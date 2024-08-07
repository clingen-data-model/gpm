<?php

namespace App\Modules\Group\Events;

use App\Events\Event;
use App\Events\AbstractEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Judgement;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class JudgementEvent extends AbstractEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Judgement $judgement)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('judgements');
    }
}
