<?php

namespace App\Modules\Group\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Carbon;

class MemberAdded extends GroupEvent
//  extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public GroupMember $groupMember)
    {
        $this->group = $this->groupMember->group;
    }

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
