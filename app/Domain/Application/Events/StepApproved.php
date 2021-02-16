<?php

namespace App\Domain\Application\Events;

use Illuminate\Support\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StepApproved extends ApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Application $application, public int $step, public Carbon $dateApproved)
    {
        //
    }

    public function getProperties():array
    {
        return [
            'date_approved' => $this->dateApproved
        ];
    }

    public function getLogEntry():string
    {
        return 'Step '.$this->step.' approved';
    }
    
    public function getLogDate():Carbon
    {
        return $this->dateApproved;
    }
    
    public function getStep()
    {
        return max(($this->application->current_step - 1), 1);
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
