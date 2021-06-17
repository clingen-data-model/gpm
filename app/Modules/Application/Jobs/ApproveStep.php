<?php

namespace App\Modules\Application\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\StepApproved;
use App\Modules\Application\Service\StepManagerFactory;
use App\Modules\Application\Exceptions\UnmetStepRequirementsException;
use App\Modules\Application\Notifications\ApplicationStepApprovedNotification;

class ApproveStep
{
    use Dispatchable;

    private Application $application;
    private Carbon $dateApproved;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $applicationUuid, 
        string $dateApproved,
        private bool $notifyContacts = false,
        private bool $notifyClingen = false
    )
    {
        $this->application = Application::findByUuidOrFail($applicationUuid);
        $this->dateApproved = $dateApproved ? Carbon::parse($dateApproved) : Carbon::now();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $stepManager = app()->make(StepManagerFactory::class)($this->application);
        
        if (! $stepManager->canApprove()) {
            throw new UnmetStepRequirementsException($this->application, $stepManager->getUnmetRequirements());
        }

        $approvedStep = $this->application->current_step;
        $this->application->addApprovalDate($approvedStep, $this->dateApproved);

        if (!$stepManager->isLastStep()) {
            $this->application->current_step++;
        }
        
        $this->application->save();

        $this->dispatchEvent($approvedStep);
        
        if ($stepManager->isLastStep()) {
            $this->application->completeApplication($this->dateApproved);
        }

        // TODO: extract to command and log an event on the person.
        if ($this->notifyContacts) {
            // if ($stepManager->isLastStep()) {
            //     Notification::send(
            //         $this->application->contacts, 
            //         new ApplicationCompletedNotification($this->application)
            //     );
            //     return;
            // }
            Notification::send(
                $this->application->contacts, 
                new ApplicationStepApprovedNotification($this->application, $approvedStep, $stepManager->isLastStep())
            );
        }
    }

    private function dispatchEvent($approvedStep)
    {
        $event = new StepApproved(
            application: $this->application, 
            step: $approvedStep, 
            dateApproved: $this->dateApproved
        );
        Event::dispatch($event);
    }
    
}
