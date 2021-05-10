<?php

namespace App\Modules\User\Events;

use Illuminate\Broadcasting\Channel;
use App\Modules\User\Events\UserEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserUpdated extends UserEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function getLogEntry(): string
    {
        return 'User updated';
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
