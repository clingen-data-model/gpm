<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Modules\Group\Events\GroupMemberEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MemberAdded extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

    public function getProperties(): ?array
    {
        return $this->groupMember->person->only('id', 'uuid', 'name', 'email', 'is_contact');
    }

    public function getEventType(): string
    {
        return 'member_added';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
