<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RetireNonCoreMembers
{
    /**
     * Retire all active memberships in the group that do NOT hold any keep-role.
     * @return int number of memberships retired
     */
    public function handle(Group $group, ?Carbon $asOf = null): int
    {
        $asOf = $asOf ?: now();

        $keep = collect(config('groups.keep_roles_on_inactivation', []))->filter()->values();

        $roleIds = Role::query()->whereIn('name', $keep)->pluck('id');

        $q = GroupMember::query()
            ->where('group_id', $group->id)
            ->whereNull('end_date') // only active
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('id', $roleIds));

        $message = sprintf('Retired via group status change (%s) on %s', optional($group->status)->name ?? 'inactive/retired', $asOf->toDateTimeString());
        return $q->update([
            'end_date' => $asOf,
            // set note to $message only when notes is NULL or empty string; otherwise leave as-is
            'notes' => DB::raw("CASE WHEN notes IS NULL OR notes = '' THEN " . DB::getPdo()->quote($message) . " ELSE notes END"),
        ]);

    }
}
