<?php

namespace App\Listeners;

use App\Models\Activity;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Auth;
use App\Modules\ExpertPanel\Events\ExpertPanelEvent;
use App\Modules\User\Models\User;

class RecordEvent
{
    private $logger;

    /**
     * Handle the event.
     *
     * @param  RecordableEvent  $event
     * @return void
     */
    public function handle(RecordableEvent $event)
    {
        $this->logger = activity($event->getLog());

        $this->addCauser();
        $this->addSubject($event);
        $this->addProperties($event);
        $this->addEventUuid($event);
        $this->logger->createdAt($event->getLogDate());
        $this->logger->log($event->getLogEntry());
    }

    private function addSubject($event): void
    {
        if ($event->hasSubject()) {
            $this->logger->performedOn($event->getSubject());
        }
    }

    private function addProperties($event): void
    {
        $properties = $event->getProperties();
        $properties['activity_type'] = $event->getActivityType();

        if ($properties) {
            if ($event instanceof ExpertPanelEvent && !isset($properties['step'])) {
                $properties['step'] = $event->getStep();
            }
        }
        $this->logger->withProperties($properties);
    }

    private function addEventUuid($event): void
    {
        $this->logger->tap(function (Activity $activity) use ($event) {
            $activity->event_uuid = $event->getEventUuid();
        });
    }

    private function addCauser()
    {
        $causer = User::find(Auth::id());
        if ($causer) {
            $this->logger->causedBy($causer);
        }
    }
}
