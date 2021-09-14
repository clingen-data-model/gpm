<?php

namespace App\Modules\Person\Events;

use Illuminate\Broadcasting\Channel;
use App\Modules\Person\Models\Person;
use Illuminate\Queue\SerializesModels;
use App\Modules\Person\Events\PersonEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ProfileUpdated extends PersonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Person $person, public array $data)
    {
    }

    public function getLogEntry(): string
    {
        return 'Profile updated.';
    }

    public function getProperties(): array
    {
        return $this->data;
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
