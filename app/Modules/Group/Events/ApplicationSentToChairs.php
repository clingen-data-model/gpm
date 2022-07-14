<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Submission;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ApplicationSentToChairs extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Group $group, 
        public Collection $comments, 
        public ?string $additionalComments = null
    )
    {
    }

    public function getProperties():array
    {
        return [
            'step' => $this->group->expertPanel->current_step,
            'additional_comments' => $this->additionalComments,
            'comment_ids' => $this->comments->pluck('id')
        ];
    }

    public function getLogEntry():string
    {
        $logEntry = 'Sent to CDWG OC Chairs: Step '.$this->group->expertPanel->current_step.' application';

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
