<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;
use App\Notifications\UserDefinedMailNotification;

class ApproveStep
{
    use Dispatchable;

    private ExpertPanel  $application;
    private Carbon $dateApproved;
    private array $ccAddresses;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $applicationUuid,
        string $dateApproved,
        private bool $notifyContacts = false,
        private ?string $subject = null,
        private ?string $body = null,
        private $attachments = []
    ) {
        $this->application = ExpertPanel::findByUuidOrFail($applicationUuid);
        $this->dateApproved = $dateApproved ? Carbon::parse($dateApproved) : Carbon::now();

        $defaultMail = (new ApplicationStepApprovedNotification($this->application, $this->application->current_step, false))->toMail($this->application->contacts->first());
        if (!$this->subject) {
            $this->subject = $defaultMail->subject;
        }
        if (!$this->body) {
            $this->body = $defaultMail->render();
        }
        $this->ccAddresses = $defaultMail->cc;
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
            Notification::send(
                $this->application->contacts,
                new UserDefinedMailNotification(
                    subject: $this->subject,
                    body: $this->body,
                    attachments: $this->attachments,
                    ccAddresses: $this->ccAddresses
                )
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
