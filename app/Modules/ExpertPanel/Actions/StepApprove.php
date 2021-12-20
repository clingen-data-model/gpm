<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Group\Actions\SubmissionApprove;
use App\Modules\ExpertPanel\Actions\NotifyContacts;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Actions\ApplicationComplete;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;

class StepApprove
{
    use AsAction;

    public function __construct(
        private NotifyContacts $notifyContactsAction,
        private ApplicationComplete $applicationCompleteAction,
        private SubmissionApprove $approveSubmission,
    ) {
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(
        string $expertPanelUuid,
        string $dateApproved,
        bool $notifyContacts = false,
        ?string $subject = null,
        ?string $body = null,
        $attachments = []
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $dateApproved = $dateApproved ? Carbon::parse($dateApproved) : Carbon::now();

        $stepManager = app()->make(StepManagerFactory::class)($expertPanel);

        if (! $stepManager->canApprove()) {
            throw new UnmetStepRequirementsException($expertPanel, $stepManager->getUnmetRequirements());
        }
        
        $expertPanel->setApprovalDate($expertPanel->current_step, $dateApproved);

        $approvedStep = $expertPanel->current_step;
        if (!$stepManager->isLastStep()) {
            $expertPanel->current_step++;
        }
        
        $expertPanel->save();

        $submission = $this->getSubmission($expertPanel, $approvedStep);
        if ($submission) {
            $this->approveSubmission->handle($submission, $dateApproved);
        }
        
        $this->dispatchEvent($expertPanel, $approvedStep, $dateApproved);

        if ($stepManager->isLastStep()) {
            $this->applicationCompleteAction->handle($expertPanel, $dateApproved);
        }

        if ($notifyContacts) {
            $this->notifyContacts($expertPanel, $subject, $body, $attachments);
        }
    }

    private function getSubmission($expertPanel, $approvedStep)
    {
        if (isset(config('submissions.types-by-step')[$approvedStep])) {
            return $expertPanel
                ->group
                ->submissions()
                ->pending()
                ->ofType(config('submissions.types-by-step')[$approvedStep]['id'])
                ->first();
        }
    }
    

    private function notifyContacts($expertPanel, $subject, $body, $attachments)
    {
        $defaultMail = (new ApplicationStepApprovedNotification($expertPanel, $expertPanel->current_step, false))
        ->toMail($expertPanel->contacts->first());

        $subject = $subject ?? $defaultMail->subject;
        $body = $body ?? $defaultMail->render();
        $ccAddresses = $defaultMail->cc;

        $this->notifyContactsAction->handle(
            expertPanel: $expertPanel,
            subject: $subject,
            body: $body,
            attachments: $attachments,
            ccAddresses: $ccAddresses
        );
    }

    private function dispatchEvent($expertPanel, $approvedStep, $dateApproved)
    {
        Event::dispatch(new StepApproved(
            application: $expertPanel,
            step: $approvedStep,
            dateApproved: $dateApproved
        ));
    }
}
