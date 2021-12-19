<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Submission;
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

class SubmissionApproved extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Submission $submission)
    {
        $this->group = $submission->group;
    }

    public function getProperties():array
    {
        return [
            'submission' => $this->submission->toArray()
        ];
    }

    public function getLogEntry():string
    {
        return 'Step '.$this->getStep().' approved';
    }
    
    public function getStep()
    {
        return max(($this->group->expertPanel->current_step - 1), 1);
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
