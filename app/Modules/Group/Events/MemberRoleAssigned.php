<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use Illuminate\Support\Collection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MemberRoleAssigned extends GroupMemberEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public GroupMember $groupMember, public Collection $roles)
    {
        parent::__construct($groupMember);
    }

    public function getLogEntry(): string
    {
        return $this->groupMember->name.' given roles '.$this->roles->pluck('name')->join(',', ', and');
    }

    public function getProperties(): ?array
    {
        return [
            'member' => $this->groupMember->person->only('id', 'name', 'email'),
            'roles' => $this->roles->pluck('name')->toArray()
        ];
    }

    public function getEventType(): string
    {
        return 'member_role_assigned';
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
