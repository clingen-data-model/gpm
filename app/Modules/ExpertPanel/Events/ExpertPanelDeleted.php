<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ExpertPanelDeleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExpertPanel $application
    ) {
    }

    public function getProperties(): array
    {
        return [];
    }

    public function getLogEntry(): string
    {
        return 'Application deleted';
    }

    // public function getLogDate():Carbon
    // {
    //     return $this->application->date_initiated;
    // }

    // public function getStep()
    // {
    //     return 1;
    // }
}
