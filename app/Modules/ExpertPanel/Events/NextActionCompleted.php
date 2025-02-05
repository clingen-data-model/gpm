<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;

class NextActionCompleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $application, public NextAction $nextAction)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Next action completed: ' . $this->nextAction->entry;
    }

    public function getProperties(): array
    {
        return [
            'next_action' => $this->nextAction->toArray(),
        ];
    }

    public function getLogDate(): Carbon
    {
        return $this->nextAction->date_completed;
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
