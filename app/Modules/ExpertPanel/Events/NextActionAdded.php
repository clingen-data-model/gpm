<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class NextActionAdded extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public NextAction $nextAction)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Added next action: ' . $this->nextAction->entry;
    }

    public function getProperties(): array
    {
        return [
            'next_action' => $this->nextAction->toArray()
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

    public function shouldPublish(): bool
    {
        return false;
    }
}
