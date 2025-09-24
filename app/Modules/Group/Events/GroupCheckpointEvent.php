<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Http\Resources\GroupExternalResource;

class GroupCheckpointEvent extends GroupEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
        return (new GroupExternalResource($this->group))->toArray(null);
    }

    public function checkpointIfNeeded(): void
    {
        // Nothing to do here, this is the checkpoint event
        // that other events trigger. What do you want to do,
        // bring the system down with recursion?
    }

    public function getLogEntry() :string
    {
        return 'Checkpoint event for group: ' . $this->group->name;
    }
}
