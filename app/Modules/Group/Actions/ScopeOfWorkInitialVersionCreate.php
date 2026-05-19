<?php

namespace App\Modules\Group\Actions;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

class ScopeOfWorkInitialVersionCreate
{
    use AsObject;

    public function handle(Group $group, ?User $user = null): ScopeOfWorkVersion
    {
        $group->loadMissing('expertPanel');

        if (!$group->expertPanel) {
            throw new \InvalidArgumentException('Scope of Work versions can only be created for Expert Panel groups.');
        }

        $existingApprovedVersion = ScopeOfWorkVersion::forGroup($group)
            ->approved()
            ->orderByDesc('major_version')
            ->orderByDesc('minor_version')
            ->first();

        if ($existingApprovedVersion) {
            return $existingApprovedVersion;
        }

        return DB::transaction(function () use ($group, $user) {
            $version = ScopeOfWorkVersion::create([
                'group_id' => $group->id,
                'expert_panel_id' => $group->expertPanel->id,
                'major_version' => config('scope_of_work.versioning.initial_major_version', 1),
                'minor_version' => config('scope_of_work.versioning.initial_minor_version', 0),
                'status' => ScopeOfWorkVersion::STATUS_APPROVED,
                'created_by' => $user?->id,
                'approved_by' => $user?->id,
                'approved_at' => $this->approvedAt($group),
            ]);

            ScopeOfWorkSnapshotCreate::run($version, $group);

            return $version->load('latestSnapshot');
        });
    }

    private function approvedAt(Group $group)
    {
        $expertPanel = $group->expertPanel;

        return $expertPanel->date_completed
            ?? $expertPanel->step_4_approval_date
            ?? $expertPanel->step_1_approval_date
            ?? now();
    }
}