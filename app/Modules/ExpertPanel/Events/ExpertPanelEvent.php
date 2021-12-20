<?php

namespace App\Modules\ExpertPanel\Events;

use DateTime;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Events\RecordableEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Type\Integer;

abstract class ExpertPanelEvent extends RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel  $application)
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
        return $this->application->group;
    }

    public function getLogDate():Carbon
    {
        return Carbon::now();
    }

    public function getStep()
    {
        return $this->application->current_step;
    }
}
