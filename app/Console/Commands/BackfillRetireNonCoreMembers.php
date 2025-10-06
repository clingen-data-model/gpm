<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Actions\RetireNonCoreMembers;

class BackfillRetireNonCoreMembers extends Command
{
    protected $signature = 'gpm:retire-non-core {groupId?}';
    protected $description = 'Retire non-core members for groups that are inactive/retired';

    public function handle(RetireNonCoreMembers $action): int
    {
        $retiredId  = data_get(config('groups.statuses.retired'), 'id', 3);
        $inactiveId = data_get(config('groups.statuses.inactive'), 'id', 5);

        $groups = Group::query()
            ->when($this->argument('groupId'), fn($q,$id) => $q->where('id',$id))
            ->whereIn('group_status_id', [$retiredId, $inactiveId])
            ->get();

        $total = 0;
        foreach ($groups as $group) {
            $count = $action->handle($group);
            $this->info("Group {$group->id}: retired {$count} members");
            $total += $count;
        }
        $this->info("Done. Total memberships retired: {$total}");
        return self::SUCCESS;
    }
}
