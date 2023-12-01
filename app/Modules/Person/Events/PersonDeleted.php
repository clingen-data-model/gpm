<?php

namespace App\Modules\Person\Events;

use App\Events\PublishableEvent;
use App\Modules\Person\Events\Traits\PublishesEvent;
use App\Modules\Person\Models\Person;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonDeleted extends PersonEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use PublishesEvent;

    public function __construct(public Person $person)
    {
    }

    public function getLogEntry(): string
    {
        return 'Person deleted.';
    }

    public function getProperties(): array
    {
        return [];
    }

    public function getEventType(): string
    {
        return 'deleted';
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
