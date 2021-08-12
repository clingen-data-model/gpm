<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StepDateApprovedUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel  $application, public int $step, public string $dateApproved)
    {
    }

    public function getLogEntry():string
    {
        return 'Approval date updated to '.$this->dateApproved.' for step '.$this->step;
    }

    public function getProperties():array
    {
        return [
            'application_uuid' => $this->application->uuid,
            'step' => $this->step,
            'new_date_approved' => $this->dateApproved,
            'old_approval_date' => $this->application->getOriginal('approval_dates')['step '.$this->step]
        ];
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
