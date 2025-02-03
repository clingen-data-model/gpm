<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Carbon;
use App\Events\RecordableEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class GroupEvent extends RecordableEvent
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

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function getTopic(): string
    {
        return config('dx.topics.outgoing.gpm-general-events');
    }

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function shouldPublish(): bool
    {
        return !$this->group->isEp || $this->group->expertPanel->isApproved();
    }

    abstract public function getLogEntry() :string;

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
