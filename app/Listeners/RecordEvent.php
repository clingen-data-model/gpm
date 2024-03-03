<?php

namespace App\Listeners;

use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\ExpertPanel\Events\ApplicationEvent;
use App\Modules\ExpertPanel\Events\ExpertPanelEvent;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;
use App\Modules\User\Models\User;

class RecordEvent
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  RecordableEvent  $event
     * @return void
     */
    public function handle(RecordableEvent $event)
    {
        $causer = User::find(Auth::id());

        $logger = activity($event->getLog());

        if ($causer) {
            $logger->causedBy($causer);
        }

        if ($event->hasSubject()) {
            $logger->performedOn($event->getSubject());
        }

        $properties = $event->getProperties();
        $properties['activity_type'] = $event->getActivityType();
            
        if ($properties) {
            if ($event instanceof ExpertPanelEvent && !isset($properties['step'])) {
                $properties['step'] = $event->getStep();
            }
        }
        $logger->withProperties($properties);

        $logger->createdAt($event->getLogDate());

        $logger->log($event->getLogEntry());
    }
}
