<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Person\Models\Person;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactRemoved extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $application, public Person $person)
    {
        //
    }

    public function getLogEntry(): string
    {
        return 'Removed contact '.$this->person->name;
    }

    public function getProperties(): array
    {
        return [
            'person' => $this->person->toArray(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    // public function broadcastOn()
    // {
    //     return new PrivateChannel('channel-name');
    // }
}
