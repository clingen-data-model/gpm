<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
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
            'member.add' => $user && ($user->hasPermissionTo('groups-manage') || $user->hasGroupPermissionTo('members-remove', $group)),
            'member.remove' => $user?->can('inviteMembers', $group) ?? false,
            'member.update_role', 'member.add_chair', 'member.remove_chair' => $user?->can('updateMembers', $group) ?? false,
            'member.retire', 'member.unretire' => $user?->can('retireMember', $group) ?? false,
            default => false,
        };
    }

    public function restore(Group $group, ScopeOfWorkChange $change, array $baseline, array $recorded, array $current): void
    {
        if (str_starts_with($change->rule_key, 'member.')) {
            $this->restoreMember($group, $change, $baseline);
            return;
        }
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

    private function restoreMember(Group $group, ScopeOfWorkChange $change, array $baseline): void
    {
        $value = $change->after_value ?? $change->before_value;
        $personId = $value['person_id'] ?? null;
        $membershipId = $value['membership_id'] ?? $value['id'] ?? null;
        abort_unless($this->validId($personId) && $this->validId($membershipId), 409,
            'The membership change has no recorded identity. Refresh and retry.');
        $members = data_get($baseline, 'scope_of_work.members');
        abort_unless(is_array($members) && array_is_list($members), 409, 'The approved membership snapshot is unavailable.');
        abort_unless(collect($members)->every(fn ($member) => is_array($member) && $this->validId($member['person_id'] ?? null)),
            409, 'The approved membership identities were not captured.');
        $matches = collect($members)->filter(fn ($member) => (string) ($member['person_id'] ?? '') === (string) $personId);
        abort_if($matches->count() > 1, 409, 'The approved membership identity is ambiguous.');
        $before = $matches->first();

        // Lock this person's records in this group, including the exact removed record.
        $records = GroupMember::withTrashed()->where('group_id', $group->id)->where('person_id', $personId)
            ->lockForUpdate()->get();
        $member = $records->firstWhere('id', $membershipId);
        abort_unless($member, 409, 'The recorded membership no longer exists in this group.');
        $live = $records->filter(fn ($record) => !$record->trashed());
        if ($change->rule_key === 'member.remove') {
            abort_unless($before && (string) ($before['id'] ?? '') === (string) $member->id
                && $member->trashed() && $live->isEmpty(), 409, 'The removed membership conflicts with current membership.');
        } else {
            abort_unless(!$member->trashed() && $live->count() === 1, 409, 'The membership identity changed. Refresh and retry.');
            if ($change->rule_key !== 'member.add') {
                abort_unless($before && (string) ($before['id'] ?? '') === (string) $member->id, 409,
                    'The approved membership identity has been replaced.');
            } else {
                abort_if($before !== null, 409, 'This membership already exists in the approved version.');
            }
        }
        $member->setRelation('roles', $member->roles()->lockForUpdate()->get());
        $currentMembers = $group->members->reject(fn ($item) => (string) $item->person_id === (string) $personId);
        if (!$member->trashed()) {
            $currentMembers->push($member);
        }
        $group->setRelation('members', $currentMembers);
        $fresh = collect(SnapshotCompare::run($group, $baseline, SnapshotBuild::run($group)))
            ->first(function ($item) use ($change, $personId, $value) {
                $payload = $item['after_value'] ?? $item['before_value'];
                return $item['rule_key'] === $change->rule_key
                    && ($item['field_name'] ?? null) === $change->field_name
                    && (string) ($payload['person_id'] ?? '') === (string) $personId
                    && ($payload['role'] ?? null) === ($value['role'] ?? null);
            });
        abort_unless($fresh && $this->storedValue($fresh['before_value']) === $this->storedValue($change->before_value)
            && $this->storedValue($fresh['after_value']) === $this->storedValue($change->after_value), 409,
            'The revision changed. Refresh and retry.');

        if ($change->rule_key === 'member.add') {
            // Keep the Person, pivots and historical snapshots. No business events.
            GroupMember::whereKey($member->id)->where('group_id', $group->id)->delete();
        } elseif ($change->rule_key === 'member.remove') {
            abort_unless(array_key_exists('end_date', $before) && isset($before['roles']) && is_array($before['roles']) && array_is_list($before['roles']),
                409, 'The approved membership dates or roles were not captured.');
            $roleIds = collect($before['roles'])->map(fn ($role) => $this->capturedRoleId($member, $role))->all();
            abort_unless(count($roleIds) === count(array_unique($roleIds)), 409, 'The approved roles are ambiguous.');
            $member->forceFill(Arr::only($before, ['start_date', 'end_date', 'notes', 'is_contact', 'training_level_1', 'training_level_2']));
            $member->deleted_at = null;
            $member->saveQuietly();
            // Whole-member removal is the only operation that restores a whole role set.
            $member->roles()->sync($roleIds);
        } elseif (in_array($change->rule_key, ['member.retire', 'member.unretire'], true)) {
            abort_unless(array_key_exists('end_date', $before), 409, 'The approved retirement state was not captured.');
            $member->forceFill(['end_date' => $before['end_date']])->saveQuietly();
        } else {
            abort_unless(isset($before['roles']) && is_array($before['roles']) && array_is_list($before['roles']), 409,
                'The approved role set was not captured.');
            $names = array_column($before['roles'], 'name');
            abort_unless(count($names) === count($before['roles']) && count($names) === count(array_unique($names)), 409,
                'The approved role identities are ambiguous.');
            $source = $change->after_value !== null ? $fresh['after_value'] : $fresh['before_value'];
            $roleId = $this->capturedRoleId($member, ['id' => $source['role_id'] ?? null, 'name' => $source['role']]);
            if ($change->after_value !== null) {
                $member->roles()->detach($roleId);
            } else {
                $member->roles()->syncWithoutDetaching([$roleId]);
            }
        }
    }

    private function capturedRoleId(GroupMember $member, array $captured): int
    {
        abort_unless(is_string($captured['name'] ?? null) && $captured['name'] !== '', 409, 'The captured role identity is unavailable.');
        $query = config('permission.models.role')::query()->where('scope', 'group')->where('name', $captured['name'])
            ->where('guard_name', $member->guardName())->lockForUpdate();
        if (isset($captured['id'])) {
            abort_unless($this->validId($captured['id']), 409, 'The captured role ID is invalid.');
            $query->whereKey($captured['id']);
        }
        $roles = $query->get();
        abort_unless($roles->count() === 1, 409, 'The captured role no longer has an unambiguous matching identity.');
        return $roles->sole()->id;
    }

    private function validId(mixed $id): bool
    {
        return (is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0;
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
