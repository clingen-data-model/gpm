<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Modules\Group\Events\GroupMemberEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;

class MemberAdded extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use IsPublishableGroupEvent;

    /**
     * CONSTRUCTOR of parent sets group instance var to $groupMember->group;
     */

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name.' added to '.$this->group->name;
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    // No additional properties beyond those in GroupMemberEvent

}
