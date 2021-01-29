<?php

namespace App\Domain\Application\Service\Listeners;

use App\Domain\Application\Events\StepApproved;
use App\Domain\Application\Service\StepManagerFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CompleteIfLastStep
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(private StepManagerFactory $stepManagerFactory)
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  StepApproved  $event
     * @return void
     */
    public function handle(StepApproved $event)
    {
        $stepManager = ($this->stepManagerFactory)($event->application);
        if ($stepManager->isLastStep()) {
            $event->application->date_completed = $event->dateApproved;
            $event->application->save();
        }
    }
}
