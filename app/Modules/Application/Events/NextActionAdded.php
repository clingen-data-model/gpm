<?php

namespace App\Modules\Application\Events;

use App\Models\NextAction;
use Illuminate\Support\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NextActionAdded extends ApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Application $application, public NextAction $nextAction)
    {
        
    }

    public function getLogEntry():string
    {
        return 'Added next action: '.$this->nextAction->entry;
    }

    public function getProperties(): array
    {
        return [
            'next_action' => $this->nextAction->toArray()
        ];
    }

    public function getLogDate():Carbon
    {
        return Carbon::parse($this->nextAction->date_created);
    }

    public function getStep()
    {
        return $this->nextAction->step;
    }
    

    

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    // public function broadcastOn()
    // {
    //     return new PrivateChannel('channel-name');
    // }
}
