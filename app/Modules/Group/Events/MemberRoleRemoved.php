<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Contracts\Role;

class MemberRoleRemoved extends GroupMemberEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public GroupMember $groupMember, public Role $role)
    {
        parent::__construct($groupMember);
    }

    public function getLogEntry(): string
    {
        return 'Removed role '.$this->role->name.' from member '.$this->groupMember->person->name.'.';
    }

    public function getProperties(): ?array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'role' => $this->role->only('id', 'name'),
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
        ];
    }

    public function getEventType(): string
    {
        return 'member_role_removed';
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
