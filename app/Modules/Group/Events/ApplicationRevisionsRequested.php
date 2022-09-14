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

class ApplicationRevisionsRequested extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Submission $submission, public ?string $comments = null)
    {
        $this->group = $submission->group;
    }

    public function getProperties():array
    {
        return [
            'submission_id' => $this->submission->id,
            'step' => $this->submission->group->expertPanel->current_step,
        ];
    }

    public function getLogEntry():string
    {
        $logEntry = 'Revisions requested for step '.$this->group->expertPanel->current_step;

        if ($this->comments) {
            $logEntry .= ":\n".$this->comments;
        }

        return $logEntry;
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
