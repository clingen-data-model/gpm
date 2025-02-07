<?php

namespace App\Modules\Person\Events;

use App\Events\PublishableEvent;
use App\Modules\Person\Models\Person;
use Illuminate\Queue\SerializesModels;
use App\Modules\Person\Events\PersonEvent;
use App\Modules\Person\Events\Traits\PublishesEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProfileUpdated extends PersonEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use PublishesEvent;

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

    public function getEventType(): string
    {
        return 'person_updated';
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
