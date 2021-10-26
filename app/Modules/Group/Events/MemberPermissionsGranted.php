<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Collection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;

class MemberPermissionsGranted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public GroupMember $groupMember, public Collection $permissions)
    {
        //
    }

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name
                . ' granted permissions '
                . $this->permissions->pluck('name')
                    ->join(',', ', and ');
    }

    public function getProperties(): array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
            'permissions' => $this->permissions->map(fn ($p) => $p->only('id', 'name'))
        ];
    }

    public function getSubject(): Model
    {
        return $this->groupMember->group;
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