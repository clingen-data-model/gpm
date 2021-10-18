<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\CoiV1 as Coi;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoiCompleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel  $application, public Coi $coi)
    {
    }

    public function getLogEntry(): String
    {
        return 'COI form completed by '.$this->coi->data->email;
    }
    
    public function getProperties(): array
    {
        return (array)$this->coi->data;
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
