<?php

namespace App\Listeners;

use App\Events\RecordableEvent;
use App\Modules\ExpertPanel\Events\ExpertPanelEvent;
use Illuminate\Support\Facades\Auth;

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
     * @return void
     */
    public function handle(RecordableEvent $event): void
    {
        $causer = Auth::user();

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
            if ($event instanceof ExpertPanelEvent && ! isset($properties['step'])) {
                $properties['step'] = $event->getStep();
            }
        }
        $logger->withProperties($properties);

        $logger->createdAt($event->getLogDate());

        $logger->log($event->getLogEntry());
    }
}
