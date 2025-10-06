<?php

namespace App\Modules\Group\Listeners;

use App\Modules\Group\Events\GroupStatusUpdated;
use App\Modules\Group\Actions\RetireNonCoreMembers;
use Illuminate\Contracts\Queue\ShouldQueue;

class RetireMembersOnGroupInactivation implements ShouldQueue
{
    public function __construct(private RetireNonCoreMembers $action) {}

    public function handle(GroupStatusUpdated $event): void
    {
        $retiredId  = data_get(config('groups.statuses.retired'),  'id', 3);
        $inactiveId = data_get(config('groups.statuses.inactive'), 'id', 5);

        if (in_array($event->newStatus->id, [$retiredId, $inactiveId], true)) {
            $this->action->handle($event->group);
        }
    }
}
