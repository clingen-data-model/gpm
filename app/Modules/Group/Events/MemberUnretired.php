<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MemberUnretired extends GroupMemberEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name.' "un-retired" from '.$this->groupMember->group->displayName;
    }

    public function getSubject(): Group
    {
        return $this->groupMember->group;
    }

    public function getProperties(): array
    {
        return [
            'group_member' => $this->groupMember,
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
            'group' => $this->groupMember->group
        ];
    }


    public function getEventType(): string
    {
        return 'member_unretired';
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
