<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild;
use App\Modules\Group\Actions\ScopeOfWork\SnapshotCompare;
use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ScopeOfWorkChangeRestorer
{
    // Persisted scope-gene attributes only; disease_entity is not a column.
    private const GENE_FIELDS = ['hgnc_id', 'gene_symbol', 'mondo_id', 'disease_name',
        'moi', 'tier', 'plan', 'gt_curation_uuid', 'date_approved'];

    public static function canDiscard(?User $user, Group $group, ScopeOfWorkChange $change): bool
    {
        return match ($change->rule_key) {
            'panel_name.rename' => $user?->hasPermissionTo('groups-manage') ?? false,
            'scope_description.update' => $user?->can('updateApplicationAttribute', $group) ?? false,
            'gene.add' => $user?->can('removeGene', $group) ?? false,
            'gene.remove' => $user?->can('addGene', $group) ?? false,
            'gene.update', 'gene.update_tier' => in_array($change->field_name, self::GENE_FIELDS, true)
                && ($user?->can('updateGene', $group) ?? false),
            default => false,
        };
    }

    public function restore(Group $group, ScopeOfWorkChange $change, array $baseline, array $recorded, array $current): void
    {
        if (str_starts_with($change->rule_key, 'gene.')) {
            $this->restoreGene($group, $change, $baseline);
            return;
        }
        $paths = match ($change->rule_key) {
            'panel_name.rename' => ['group.name', 'expert_panel.long_base_name', 'expert_panel.short_base_name'],
            'scope_description.update' => ['scope_of_work.scope_description'],
            default => throw ValidationException::withMessages(['change' => 'This change cannot yet be discarded individually.']),
        };

        foreach ($paths as $path) {
            if (!Arr::has($baseline, $path)) {
                throw ValidationException::withMessages(['baseline' => "The approved snapshot is missing {$path}."]);
            }
            abort_unless(Arr::has($recorded, $path) && Arr::has($current, $path)
                && data_get($recorded, $path) === data_get($current, $path), 409,
                'The revision changed. Refresh and retry.');
        }

        if ($change->rule_key === 'panel_name.rename') {
            $group->update(['name' => data_get($baseline, 'group.name')]);
            $group->expertPanel->update([
                'long_base_name' => data_get($baseline, 'expert_panel.long_base_name'),
                'short_base_name' => data_get($baseline, 'expert_panel.short_base_name'),
            ]);
        } else {
            $group->expertPanel->update(['scope_description' => data_get($baseline, 'scope_of_work.scope_description')]);
        }
    }

    private function restoreGene(Group $group, ScopeOfWorkChange $change, array $baseline): void
    {
        $id = $change->after_value['id'] ?? $change->before_value['id'] ?? null;
        abort_unless(is_int($id) || (is_string($id) && ctype_digit($id)), 409,
            'The gene change has no recorded identity. Refresh and retry.');
        $gene = Gene::withTrashed()->where('expert_panel_id', $group->expertPanel->id)
            ->whereKey($id)->lockForUpdate()->first();
        abort_unless($gene, 409, 'The recorded scope gene no longer exists in this panel.');
        $genes = data_get($baseline, 'scope_of_work.scope_genes');
        if (!is_array($genes)) {
            throw ValidationException::withMessages(['baseline' => 'The approved gene snapshot is unavailable.']);
        }
        $matches = collect($genes)->filter(fn ($row) => (string) ($row['id'] ?? '') === (string) $id);
        abort_if($matches->count() > 1, 409, 'The approved gene identity is ambiguous.');
        $before = $matches->first();
        if ($before && isset($before['created_at'])) {
            abort_unless($before['created_at'] === $gene->created_at?->toISOString(), 409,
                'The recorded scope-gene identity has changed.');
        }
        $field = $change->field_name;
        if (in_array($change->rule_key, ['gene.update', 'gene.update_tier'], true)) {
            if (!in_array($field, self::GENE_FIELDS, true) || !is_array($before) || !array_key_exists($field, $before)) {
                throw ValidationException::withMessages(['baseline' => 'The approved gene field is unavailable or unsupported.']);
            }
        }

        // Reuse change generation against locked, fresh saved state. Field changes
        // compare only their own payload, so unrelated edits on the same gene survive.
        // Use the locking read for this row even under repeatable-read isolation.
        $liveGenes = $group->expertPanel->genes->reject(fn ($row) => $row->id === $gene->id);
        if (!$gene->trashed()) {
            $liveGenes->push($gene);
        }
        $group->expertPanel->setRelation('genes', $liveGenes);
        $fresh = collect(SnapshotCompare::run($group, $baseline, SnapshotBuild::run($group)))
            ->first(fn ($item) => $item['rule_key'] === $change->rule_key
                && ($item['field_name'] ?? null) === $field
                && (string) ($item['after_value']['id'] ?? $item['before_value']['id'] ?? '') === (string) $id);
        abort_unless($fresh && $this->storedValue($fresh['before_value']) === $this->storedValue($change->before_value)
            && $this->storedValue($fresh['after_value']) === $this->storedValue($change->after_value), 409,
            'The revision changed. Refresh and retry.');

        if ($change->rule_key === 'gene.add') {
            abort_if($before !== null || $gene->trashed(), 409);
            // Query soft-delete deliberately bypasses Gene::deleting, which deletes
            // captured gene snapshots. No application actions or DX events are fired.
            Gene::where('expert_panel_id', $group->expertPanel->id)->whereKey($id)->delete();
        } elseif ($change->rule_key === 'gene.remove') {
            abort_unless($before !== null && $gene->trashed(), 409);
            $gene->fill(Arr::only($before, self::GENE_FIELDS));
            $gene->restore();
        } else {
            abort_if($gene->trashed(), 409);
            $gene->update([$field => $before[$field]]);
        }
    }

    private function storedValue(mixed $value): mixed
    {
        // Match persisted JSON dates and ignore object-key ordering (MySQL may
        // reorder JSON keys). Preserve scalar types and ordered list contents.
        $value = json_decode(json_encode($value), true);
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value);
        }
        return array_map(fn ($item) => $this->storedValue($item), $value);
    }
}
