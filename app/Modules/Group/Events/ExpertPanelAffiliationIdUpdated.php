<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;

class ExpertPanelAffiliationIdUpdated extends GroupEvent implements PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels, IsPublishableApplicationEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Group $group,
        public $affiliationId,
    ) {
    }

    public function getLogEntry(): string
    {
        return 'EP affiliation_id set to '.$this->affiliationId.'.';
    }
    
    public function getProperties(): ?array
    {
        $properties = [
            'affiliation_id' => $this->affiliationId
        ];
        return count($properties) > 0 ? $properties : null;
    }

    public function getEventType(): string
    {
        return 'ep_info_updated';
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
