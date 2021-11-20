<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ParentUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Group $parent, public ?Group $oldParent)
    {
    }

    public function getLogEntry(): string
    {
        $oldParentName = $this->oldParent ? $this->oldParent->name : 'none';
        return 'Parent changed from '.$oldParentName.' to '.$this->parent->name.'.';
    }
    
    public function getProperties(): ?array
    {
        return [
            'new_parent' => $this->parent,
            'old_parent' => $this->oldParent,
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
