<?php

namespace App\Modules\ExpertPanel\Events;

use DateTime;
use App\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;

class ApplicationInitiated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExpertPanel $application
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

    public function getStep()
    {
        return 1;
    }

    static public function fromActivity(Activity $activity):self
    {
        $subject = $activity->subject;
        if ($subject instanceof Group) {
            $subject = $subject->expertPanel;
        }
        return new self(
            application: $subject
        );
        
    }
    
}
