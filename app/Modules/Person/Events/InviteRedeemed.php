<?php

namespace App\Modules\Person\Events;

use App\Modules\User\Models\User;
use Illuminate\Broadcasting\Channel;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Queue\SerializesModels;
use App\Modules\Person\Events\PersonEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class InviteRedeemed extends PersonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Person $person;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Invite $invite, public User $user)
    {
        $this->person = $this->invite->person;
    }

    public function getLogEntry(): string
    {
        return "Invite was redeemed";
    }

    public function getProperties(): array
    {
        return [
            'user' => $this->user->only('id', 'name', 'email')
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
