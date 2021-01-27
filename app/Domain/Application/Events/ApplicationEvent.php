<?php

namespace App\Domain\Application\Events;

use DateTime;
use Illuminate\Queue\SerializesModels;
use App\Domain\Application\Models\Application;
use App\Events\RecordableEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ApplicationEvent extends RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Application $application)
    {
    }

    public function getLog():string
    {
        return 'applications';
    }
    
    public function hasSubject():bool
    {
        return true;
    }

    public function getSubject():Model
    {
        return $this->application;
    }

    public function getLogDate():Carbon
    {
        return Carbon::now();
    }
    
    
}
