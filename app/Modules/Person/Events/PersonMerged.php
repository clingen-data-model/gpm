<?php

namespace App\Modules\Person\Events;

use App\Modules\Person\Models\Person;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonMerged extends PersonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Person $authority, public Person $obsolete)
    {
    }

    public function getLog(): string
    {
        return 'people';
    }

    public function getLogEntry(): string
    {
        return $this->obsolete->name.' was merged into '.$this->authority->name;
    }

    public function getSubject(): Model
    {
        return $this->authority;
    }

    public function getProperties(): array
    {
        return [
            'authority' => $this->authority,
            'obsolete' => $this->obsolete,
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
