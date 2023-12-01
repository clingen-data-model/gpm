<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ApplicationCompleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $application)
    {
    }

    public function getLogEntry(): string
    {
        return 'Application completed.';
    }

    public function getLogDate(): Carbon
    {
        return $this->application->date_completed;
    }

    public function getProperties(): array
    {
        return ['date_completed' => $this->application->date_completed];
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
