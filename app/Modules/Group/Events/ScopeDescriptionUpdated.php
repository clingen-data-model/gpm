<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Events\Traits\IsPublishableExpertPanelEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

// TODO: should probably extend ExpertPanelEvent, since only EPs have scope descriptions.
// or else, descriptions should be added to other groups...
class ScopeDescriptionUpdated extends GroupEvent implements PublishableExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels, IsPublishableExpertPanelEvent;

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
