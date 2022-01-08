<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Spatie\Permission\Contracts\Role;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Modules\Groups\Events\PublishableApplicationEvent;

class MemberRoleRemoved extends GroupMemberEvent implements PublishableApplicationEvent
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
