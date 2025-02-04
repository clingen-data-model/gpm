<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GroupDescriptionUpdated extends GroupEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels, IsPublishableGroupEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public ?string $description)
    {
        //
    }

    public function getLogEntry():string
    {
        return 'Scope description updated.';
    }

    public function getProperties():array
    {
        return ['scope_description' => $this->description];
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
