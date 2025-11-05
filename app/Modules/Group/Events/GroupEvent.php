<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Events\RecordableEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Events\PublishableEvent;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class GroupEvent extends RecordableEvent implements PublishableEvent
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

    public function hasSubject(): bool
    {
        return true;
    }

    public function getSubject(): Model
    {
        return $this->group;
    }

    public function getLog(): string
    {
        return 'groups';
    }

    public function getProperties(): ?array
    {
        return null;
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

    public function getEventType(): string
    {
        return Str::snake((new \ReflectionClass($this))->getShortName());
    }

    public function getPublishableMessage(): array {
        $message = $this->getProperties() ?? [];
        $message['group'] = $this->mapGroupForMessage($this->group, false, false);
        return $message;
    }

    abstract public function getLogEntry() :string;

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
