<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;

class GroupCheckpointEvent extends GroupEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels, IsPublishableGroupEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group)
    {
    }

    public function getSchemaVersion(): string
    {
        return '2.0.0';
    }

    public function getTopic(): string
    {
        return config('dx.topics.outgoing.gpm-checkpoint-events');
    }

    public function getPublishableMessage(): array {
        return $this->mapGroupForMessage($this->group);
    }

    public function getLogEntry() :string
    {
        return 'Checkpoint event for group: ' . $this->group->name;
    }
}
