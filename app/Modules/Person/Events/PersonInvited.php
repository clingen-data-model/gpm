<?php

namespace App\Modules\Person\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PersonInvited extends PersonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public ?Model $inviter;
    public Person $person;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Invite $invite)
    {
        $this->inviter = $invite->inviter;
        $this->person = $invite->person;
    }

    public function getLog(): string
    {
        return 'people';
    }

    public function getLogEntry(): string
    {
        if ($this->inviter) {
            return 'Invited to join '.$this->inviter->name;
        }
        if (Auth::user()) {
            return 'Invited by '.Auth::user()->name;
        }
        
        return 'Invited by system';
    }

    public function getSubject(): Model
    {
        return $this->person;
    }

    public function getProperties(): array
    {
        return [
            'first_name' => $this->invite->first_name,
            'last_name' => $this->invite->last_name,
            'email' => $this->invite->email,
            'inviter_type' => $this->invite->inviter_type,
            'inviter_id' => $this->invite->inviter_id,
            'person_id' => $this->invite->person_id
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
