<?php

namespace App\Domain\Application\Events;

use DateTime;
use Illuminate\Queue\SerializesModels;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Carbon;

class ApplicationInitiated extends ApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Application $application
    )
    {}

    public function getProperties():array
    {
        return $this->application->getAttributes();
    }

    public function getLogEntry():string
    {
        return 'Application initiated';
    }
    
    public function getLogDate():Carbon
    {
        return $this->application->date_initiated;
    }
    
}
