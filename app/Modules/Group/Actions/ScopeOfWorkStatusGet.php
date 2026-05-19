<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Lorisleiva\Actions\Concerns\AsObject;

class ScopeOfWorkStatusGet
{
    use AsObject;

    public function handle(Group $group): array
    {
        $approvedVersion = ScopeOfWorkVersion::forGroup($group)
            ->approved()
            ->with('latestSnapshot')
            ->orderByDesc('major_version')
            ->orderByDesc('minor_version')
            ->first();

        $activeRevision = ScopeOfWorkVersion::forGroup($group)
            ->whereIn('status', [
                ScopeOfWorkVersion::STATUS_DRAFT,
                ScopeOfWorkVersion::STATUS_SUBMITTED,
                ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED,
            ])
            ->with(['changes', 'latestSnapshot', 'baseVersion'])
            ->latest()
            ->first();

        return [
            'has_approved_version' => (bool) $approvedVersion,
            'approved_version' => $approvedVersion
                ? $this->versionPayload($approvedVersion)
                : null,

            'has_active_revision' => (bool) $activeRevision,
            'active_revision' => $activeRevision
                ? $this->revisionPayload($activeRevision)
                : null,
        ];
    }

    private function versionPayload(ScopeOfWorkVersion $version): array
    {
        return [
            'id' => $version->id,
            'uuid' => $version->uuid,
            'version_label' => $version->version_label,
            'major_version' => $version->major_version,
            'minor_version' => $version->minor_version,
            'status' => $version->status,
            'approved_at' => optional($version->approved_at)->toISOString(),
            'created_at' => optional($version->created_at)->toISOString(),
        ];
    }

    private function revisionPayload(ScopeOfWorkVersion $revision): array
    {
        $changes = $revision->changes;

        return [
            'id' => $revision->id,
            'uuid' => $revision->uuid,
            'version_label' => $revision->version_label,
            'major_version' => $revision->major_version,
            'minor_version' => $revision->minor_version,
            'status' => $revision->status,

            'base_version' => $revision->baseVersion
                ? $this->versionPayload($revision->baseVersion)
                : null,

            'summary' => [
                'total_changes' => $changes->count(),
                'major_changes' => $changes->where('impact', ScopeOfWorkChange::IMPACT_MAJOR)->count(),
                'minor_changes' => $changes->where('impact', ScopeOfWorkChange::IMPACT_MINOR)->count(),
                'approval_required_changes' => $changes
                    ->whereIn('requires_approval', [
                        ScopeOfWorkChange::APPROVAL_YES,
                        ScopeOfWorkChange::APPROVAL_CONDITIONAL,
                    ])
                    ->count(),
                'conditional_changes' => $changes
                    ->where('requires_approval', ScopeOfWorkChange::APPROVAL_CONDITIONAL)
                    ->count(),
                'can_finalize_without_approval' => $changes->isNotEmpty()
                    && $changes->every(fn ($change) => $change->requires_approval === ScopeOfWorkChange::APPROVAL_NO),
                'requires_submission' => $changes->contains(fn ($change) => in_array($change->requires_approval, [
                    ScopeOfWorkChange::APPROVAL_YES,
                    ScopeOfWorkChange::APPROVAL_CONDITIONAL,
                ], true)),
            ],

            'changes' => $changes
                ->sortBy([
                    ['impact', 'asc'],
                    ['area', 'asc'],
                    ['entity_label', 'asc'],
                ])
                ->values()
                ->map(fn (ScopeOfWorkChange $change) => $this->changePayload($change))
                ->all(),

            'created_at' => optional($revision->created_at)->toISOString(),
            'submitted_at' => optional($revision->submitted_at)->toISOString(),
            'approved_at' => optional($revision->approved_at)->toISOString(),
        ];
    }

    private function changePayload(ScopeOfWorkChange $change): array
    {
        return [
            'id' => $change->id,
            'rule_key' => $change->rule_key,
            'area' => $change->area,
            'change_type' => $change->change_type,
            'label' => $change->label,

            'entity_type' => $change->entity_type,
            'entity_uuid' => $change->entity_uuid,
            'entity_label' => $change->entity_label,
            'field_name' => $change->field_name,

            'before_value' => $change->before_value,
            'after_value' => $change->after_value,

            'impact' => $change->impact,
            'requires_approval' => $change->requires_approval,
            'approval_step' => $change->approval_step,
            'approvers' => $change->approvers,
            'condition' => $change->condition,
        ];
    }
}