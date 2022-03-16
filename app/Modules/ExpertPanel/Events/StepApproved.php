<?php

namespace App\Modules\ExpertPanel\Events;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Modules\Groups\Events\PublishableApplicationEvent;

class StepApproved extends ExpertPanelEvent implements PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel  $application, public int $step, public Carbon $dateApproved)
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
    
    public function getEventType(): string
    {
        switch ($this->step) {
            case 1:
                return 'ep_definition_approved';
            case 2:
                return 'vcep_draft_specifications_approved';
            case 3:
                return 'vcep_pilot_approved';
            case 4:
                return 'ep_final_approval';
            default:
                throw new Exception('Invalid step approved expected 1-4, received '.$this->step);
        }
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
