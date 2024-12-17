<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Carbon;

class ExpertPanelDeleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExpertPanel  $expertPanel
    ) {
    }

    public function getProperties():array
    {
        return [];
    }

    public function getLogEntry():string
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
