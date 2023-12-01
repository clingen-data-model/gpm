<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MemberAdded extends GroupMemberEvent implements PublishableEvent
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
