<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\GroupMember;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MemberPermissionsGranted extends GroupMemberEvent implements PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public GroupMember $groupMember, public Collection $permissions)
    {
        parent::__construct($groupMember);
    }

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name
                .' granted permissions '
                .$this->permissions->pluck('name')
                    ->join(',', ', and ');
    }

    public function getProperties(): array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
            'permissions' => $this->permissions->map(fn ($p) => $p->only('id', 'name')),
        ];
    }

    public function getSubject(): Model
    {
        return $this->groupMember->group;
    }

    public function getEventType(): string
    {
        return 'member_permission_granted';
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
