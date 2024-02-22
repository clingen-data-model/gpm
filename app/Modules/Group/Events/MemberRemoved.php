<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MemberRemoved extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name.' removed from group on '.$this->groupMember->end_date->format('Y-m-d');
    }

    public function getSubject(): Group
    {
        return $this->groupMember->group;
    }

    public function getProperties(): array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
            'end_date' => $this->groupMember->end_date->toAtomString()
        ];
    }

    public function getEventType(): string
    {
        return 'member_removed';
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
