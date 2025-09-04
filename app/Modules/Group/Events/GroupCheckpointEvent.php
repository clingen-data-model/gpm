<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
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

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getTopic(): string
    {
        return config('dx.topics.outgoing.gpm-general-events');
    }

    public function shouldPublish(): bool
    {
        // EP events are only publishable after definition is approved
        return !$this->group->isEp || $this->group->expertPanel->definitionIsApproved;
    }

    public function getPublishableMessage(): array {
        return (new GroupExternalResource($this->group))->toArray(null);
    }

    public function getLogEntry() :string
    {
        return 'Checkpoint event for group: ' . $this->group->name;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('group-events');
    }
}
