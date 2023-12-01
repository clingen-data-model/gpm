<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class NextActionUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $application, public NextAction $nextAction, public array $oldData)
    {
    }

    public function getLogEntry(): string
    {
        return 'Updated next action '.$this->nextAction->id;
    }

    public function getProperties(): array
    {
        return [
            'next_action' => $this->nextAction->toArray(),
            'previous_data' => $this->oldData,
        ];
    }

    public function getLogDate(): Carbon
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
