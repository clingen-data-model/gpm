<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;
use App\Events\RecordableEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Carbon;
use phpDocumentor\Reflection\Types\Boolean;

abstract class UserEvent extends RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public User $user)
    {
        //
    }

    public function hasSubject(): bool
    {
        return true;
    }
    

    public function getDescription():string
    {
        return 'User Created';
    }
    
    public function getSubject(): Model
    {
        return $this->user;
    }

    public function getProperties():?array
    {
        return $this->user->getAttributes();
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getLog(): String
    {
        return 'users';
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
