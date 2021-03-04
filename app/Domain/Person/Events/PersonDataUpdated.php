<?php

namespace App\Domain\Person\Events;

use App\Domain\Person\Models\Person;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Domain\Person\Events\PersonEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PersonDataUpdated extends PersonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Person $person, public array $attributes)
    {
        //
    }

    public function getLogEntry():string
    {
        $parts = [];
        foreach ($this->attributes as $key=>$value) {
            $parts[] = $key.' = '.$value;
        }

        return 'Attributes updated: '.implode('; ', $parts);           
    }

    public function getProperties():array
    {
        return $this->attributes;
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
