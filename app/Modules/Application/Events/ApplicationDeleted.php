<?php

namespace App\Modules\Application\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Carbon;

class ApplicationDeleted extends ApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Application $application
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
