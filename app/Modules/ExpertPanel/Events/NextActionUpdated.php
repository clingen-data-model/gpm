<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;

class NextActionUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public NextAction $nextAction, public array $oldData)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Updated next action ' . $this->nextAction->id;
    }

    public function getProperties(): array
    {
        return [
            'next_action' => $this->nextAction->toArray(),
            'previous_data' => $this->oldData
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
