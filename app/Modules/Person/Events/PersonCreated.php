<?php

namespace App\Modules\Person\Events;

use App\Events\PublishableEvent;
use App\Modules\Person\Events\Traits\PublishesEvent;
use App\Modules\Person\Models\Person;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PersonCreated extends PersonEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use PublishesEvent;

    public function __construct(public Person $person)
    {
    }

    public function getLogEntry():string
    {
        return 'Person created.';
    }

    public function getProperties(): array
    {
        return $this->person->getAttributes();
    }

    public function getEventType(): string
    {
        return 'person_created';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('person-events');
    }
}
