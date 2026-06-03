<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Group\Models\GroupMember;

class RevisionDiscard
{
    use AsObject;
    use AsController;

    public function handle(Group $group, ScopeOfWorkVersion $revision): ScopeOfWorkVersion
    {
        if ($revision->group_id !== $group->id) {
            abort(404);
        }

        if (!in_array($revision->status, [
            ScopeOfWorkVersion::STATUS_DRAFT,
            ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED,
        ], true)) {
            throw ValidationException::withMessages([
                'revision' => 'Only draft or revisions-requested Scope of Work revisions can be discarded.',
            ]);
        }

        $revision->loadMissing('baseVersion.latestSnapshot');

        if (!$revision->baseVersion?->latestSnapshot) {
            throw ValidationException::withMessages([
                'revision' => 'This Scope of Work revision does not have a base snapshot to restore from.',
            ]);
        }

        $snapshot = $revision->baseVersion->latestSnapshot->snapshot;

        return DB::transaction(function () use ($group, $revision, $snapshot) {
            $this->restoreGroupAndExpertPanel($group, $snapshot);
            $this->restoreScopeGenes($group, $snapshot);
            $this->restoreMembers($group, $snapshot);

            $revision->update([
                'status' => ScopeOfWorkVersion::STATUS_DISCARDED,
            ]);

            $revision->changes()->delete();
            $revision->snapshots()->delete();

            return $revision->fresh();
        });
    }

    public function asController(Group $group, ScopeOfWorkVersion $scopeOfWorkVersion)
    {
        $this->handle($group, $scopeOfWorkVersion);

        return StatusGet::run($group->fresh());
    }

    private function restoreGroupAndExpertPanel(Group $group, array $snapshot): void
    {
        $groupPayload = data_get($snapshot, 'group', []);
        $scopePayload = data_get($snapshot, 'scope_of_work', []);
        $expertPanelPayload = data_get($snapshot, 'expert_panel', []);

        $group->update([
            'name' => $scopePayload['panel_name'] ?? $groupPayload['name'] ?? $group->name,
        ]);

        if ($group->expertPanel) {
            $group->expertPanel->update([
                'long_base_name' => $expertPanelPayload['long_base_name'] ?? $group->expertPanel->long_base_name,
                'short_base_name' => $expertPanelPayload['short_base_name'] ?? $group->expertPanel->short_base_name,
                'scope_description' => $scopePayload['scope_description'] ?? null,
            ]);
        }
    }

    private function restoreScopeGenes(Group $group, array $snapshot): void
    {
        $group->loadMissing('expertPanel');

        if (!$group->expertPanel) {
            return;
        }

        $snapshotGenes = collect(data_get($snapshot, 'scope_of_work.scope_genes', []));
        $snapshotIds = $snapshotGenes->pluck('id')->filter()->values();

        $group->expertPanel->genes()
            ->whereNotIn('id', $snapshotIds)
            ->delete();

        foreach ($snapshotGenes as $geneData) {
            $gene = Gene::withTrashed()->find($geneData['id']);

            if (!$gene) {
                $gene = new Gene();
                $gene->id = $geneData['id'];
            }

            $gene->fill([
                'expert_panel_id' => $group->expertPanel->id,
                'hgnc_id' => $geneData['hgnc_id'] ?? null,
                'gene_symbol' => $geneData['gene_symbol'] ?? null,
                'mondo_id' => $geneData['mondo_id'] ?? null,
                'disease_name' => $geneData['disease_name'] ?? null,
                'moi' => $geneData['moi'] ?? null,
                'tier' => $geneData['tier'] ?? null,
                'plan' => $geneData['plan'] ?? null,
                'gt_curation_uuid' => $geneData['gt_curation_uuid'] ?? null,
                'date_approved' => $geneData['date_approved'] ?? null,
            ]);

            $gene->save();

            if (method_exists($gene, 'restore') && $gene->trashed()) {
                $gene->restore();
            }
        }
    }

    private function restoreMembers(Group $group, array $snapshot): void
    {
        $snapshotMembers = collect(data_get($snapshot, 'scope_of_work.members', []));
        $snapshotIds = $snapshotMembers->pluck('id')->filter()->values();

        GroupMember::where('group_id', $group->id)
            ->whereNotIn('id', $snapshotIds)
            ->delete();

        foreach ($snapshotMembers as $memberData) {
            $member = GroupMember::withTrashed()->find($memberData['id']);

            if (!$member) {
                $member = new GroupMember();
                $member->id = $memberData['id'];
            }

            $member->fill([
                'group_id' => $group->id,
                'person_id' => $memberData['person_id'],
                'start_date' => $memberData['start_date'] ?? null,
                'end_date' => $memberData['end_date'] ?? null,
                'is_contact' => $memberData['is_contact'] ?? false,

                'notes' => $memberData['notes'] ?? null,
                'training_level_1' => $memberData['training_level_1'] ?? false,
                'training_level_2' => $memberData['training_level_2'] ?? false,
            ]);

            $member->save();

            if (method_exists($member, 'restore') && $member->trashed()) {
                $member->restore();
            }

            $roleIds = collect($memberData['roles'] ?? [])
                ->pluck('id')
                ->filter()
                ->values()
                ->all();

            $member->roles()->sync($roleIds);
        }
    }
}