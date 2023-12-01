<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoiCompleted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Coi $coi)
    {
    }

    public function getLogEntry(): string
    {
        return ($this->coi->groupMember)
            ? 'COI form completed by '.$this->coi->groupMember->person->email
            : 'Legacy COI uploaded';
    }

    public function getProperties(): array
    {
        return (array) $this->coi->data;
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
