<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Lorisleiva\Actions\Concerns\AsObject;

class HistoryGet
{
    use AsObject;

    public function handle(Group $group): array
    {
        $versions = ScopeOfWorkVersion::forGroup($group)
            ->with([
                'changes',
                'baseVersion',
                'submission',
                'submission.status',
                'submission.type',
                'latestSnapshot',
                'creator',
                'submitter',
                'approver',
            ])
            ->where('status', '!=', ScopeOfWorkVersion::STATUS_DISCARDED)
            ->orderByDesc('major_version')
            ->orderByDesc('minor_version')
            ->orderByDesc('created_at')
            ->get();

        return [
            'group_id' => $group->id,
            'group_uuid' => $group->uuid,
            'versions' => $versions
                ->map(fn (ScopeOfWorkVersion $version) => $this->versionPayload($version))
                ->values()
                ->all(),
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

            'base_version' => $version->baseVersion
                ? [
                    'id' => $version->baseVersion->id,
                    'uuid' => $version->baseVersion->uuid,
                    'version_label' => $version->baseVersion->version_label,
                ]
                : null,

            'submission' => $version->submission
                ? [
                    'id' => $version->submission->id,
                    'status' => $version->submission->status?->name,
                    'type' => $version->submission->type?->name,
                    'created_at' => optional($version->submission->created_at)->toISOString(),
                    'closed_at' => optional($version->submission->closed_at)->toISOString(),
                ]
                : null,

            'summary' => [
                'total_changes' => $version->changes->count(),
                'major_changes' => $version->changes
                    ->where('impact', ScopeOfWorkChange::IMPACT_MAJOR)
                    ->count(),
                'minor_changes' => $version->changes
                    ->where('impact', ScopeOfWorkChange::IMPACT_MINOR)
                    ->count(),
                'approval_required_changes' => $version->changes
                    ->whereIn('requires_approval', [
                        ScopeOfWorkChange::APPROVAL_YES,
                        ScopeOfWorkChange::APPROVAL_CONDITIONAL,
                    ])
                    ->count(),
            ],

            'changes' => $version->changes
                ->map(fn (ScopeOfWorkChange $change) => [
                    'id' => $change->id,
                    'rule_key' => $change->rule_key,
                    'label' => $change->label,
                    'area' => $change->area,
                    'change_type' => $change->change_type,
                    'entity_type' => $change->entity_type,
                    'entity_label' => $change->entity_label,
                    'field_name' => $change->field_name,
                    'impact' => $change->impact,
                    'requires_approval' => $change->requires_approval,
                    'approval_step' => $change->approval_step,
                    'approvers' => $change->approvers,
                ])
                ->values()
                ->all(),

            'created_by' => $version->creator?->name,
            'submitted_by' => $version->submitter?->name,
            'approved_by' => $version->approver?->name,

            'created_at' => optional($version->created_at)->toISOString(),
            'submitted_at' => optional($version->submitted_at)->toISOString(),
            'approved_at' => optional($version->approved_at)->toISOString(),
        ];
    }
}