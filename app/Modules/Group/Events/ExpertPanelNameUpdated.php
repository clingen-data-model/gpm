<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ExpertPanelNameUpdated extends GroupEvent implements PublishableEvent
{
    use Dispatchable, 
        InteractsWithSockets, 
        SerializesModels, 
        IsPublishableApplicationEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Group $group,
        public ?String $longName,
        public ?string $shortName,
        public ?String $oldLong,
        public ?String $oldShort
    ) {
    }

    public function getLogEntry(): string
    {
        return 'EP name updated.';
    }
    
    public function getProperties(): ?array
    {
        $properties = [];
        if ($this->oldLong != $this->longName) {
            $properties['old_long_base_name'] = $this->oldLong;
            $properties['new_long_base_name'] = $this->longName;
        }
        if ($this->oldShort != $this->shortName) {
            $properties['old_short_base_name'] = $this->oldShort;
            $properties['new_short_base_name'] = $this->shortName;
        }
        
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
