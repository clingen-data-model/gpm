<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

class VersionCompareGet
{
    use AsObject;

    public function handle(Group $group, ScopeOfWorkVersion $fromVersion, ScopeOfWorkVersion $toVersion): array
    {
        if ($fromVersion->group_id !== $group->id || $toVersion->group_id !== $group->id) {
            abort(404);
        }

        $fromVersion->loadMissing('latestSnapshot');
        $toVersion->loadMissing('latestSnapshot');

        if (!$fromVersion->latestSnapshot || !$toVersion->latestSnapshot) {
            throw ValidationException::withMessages([
                'versions' => 'Both Scope of Work versions must have snapshots before they can be compared.',
            ]);
        }

        $changes = ScopeOfWorkSnapshotCompare::run(
            $group,
            $fromVersion->latestSnapshot->snapshot,
            $toVersion->latestSnapshot->snapshot
        );

        return [
            'group_id' => $group->id,
            'group_uuid' => $group->uuid,

            'from_version' => $this->versionPayload($fromVersion),
            'to_version' => $this->versionPayload($toVersion),

            'summary' => [
                'total_changes' => count($changes),
                'major_changes' => collect($changes)->where('impact', 'major')->count(),
                'minor_changes' => collect($changes)->where('impact', 'minor')->count(),
                'approval_required_changes' => collect($changes)
                    ->whereIn('requires_approval', ['yes', 'conditional'])
                    ->count(),
            ],

            'changes' => $changes,
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
            'created_at' => optional($version->created_at)->toISOString(),
            'submitted_at' => optional($version->submitted_at)->toISOString(),
            'approved_at' => optional($version->approved_at)->toISOString(),
        ];
    }
}