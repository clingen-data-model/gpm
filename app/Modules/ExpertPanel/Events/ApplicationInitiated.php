<?php

namespace App\Modules\ExpertPanel\Events;

use DateTime;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Carbon;

class ApplicationInitiated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExpertPanel  $expertPanel
    )
    {}

    public function getProperties():array
    {
        return $this->expertPanel->getAttributes();
    }

    public function getLogEntry():string
    {
        return 'Application initiated';
    }
    
    public function getLogDate():Carbon
    {
        return $this->expertPanel->date_initiated;
    }

    public function getStep()
    {
        return 1;
    }
    
}
