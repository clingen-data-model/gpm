<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Broadcasting\PrivateChannel;
use Spatie\Permission\Contracts\Permission;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MemberPermissionRevoked extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public GroupMember $groupMember, public Permission $permission)
    {
        parent::__construct($groupMember);
    }

    public function getSubject(): Model
    {
        return $this->groupMember->group;
    }

    public function getLogEntry(): string
    {
        return 'Permission '.$this->permission->name.' revoked from member '.$this->groupMember->person->name.'.';
    }

    public function getProperties(): array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'permission' => $this->permission->only('id', 'name'),
            'person' => $this->groupMember->person->only('id', 'name', 'email')
        ];
    }

    public function getEventType(): string
    {
        return 'member_permission_revoked';
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
