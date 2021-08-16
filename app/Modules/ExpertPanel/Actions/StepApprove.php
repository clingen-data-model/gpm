<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\ExpertPanel\Actions\NotifyContacts;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Actions\ApplicationComplete;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;

class StepApprove
{
    use AsAction;

    public function __construct(private NotifyContacts $notifyContactsAction, private ApplicationComplete $applicationCompleteAction)
    {
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

        $defaultMail = (new ApplicationStepApprovedNotification($expertPanel, $expertPanel->current_step, false))
                        ->toMail($expertPanel->contacts->first());

        $subject = $subject ?? $defaultMail->subject;
        $body = $body ?? $defaultMail->render();
        $ccAddresses = $defaultMail->cc;

        $stepManager = app()->make(StepManagerFactory::class)($expertPanel);
        
        if (! $stepManager->canApprove()) {
            throw new UnmetStepRequirementsException($expertPanel, $stepManager->getUnmetRequirements());
        }

        $approvedStep = $expertPanel->current_step;
        $expertPanel->addApprovalDate($approvedStep, $dateApproved);

        if (!$stepManager->isLastStep()) {
            $expertPanel->current_step++;
        }
        
        $expertPanel->save();

        Event::dispatch(new StepApproved(
            application: $expertPanel,
            step: $approvedStep,
            dateApproved: $dateApproved
        ));
        
        if ($stepManager->isLastStep()) {
            $this->applicationCompleteAction->handle($expertPanel, $dateApproved);
        }

        // TODO: extract to command and log an event on the person.
        if ($notifyContacts) {
            $this->notifyContactsAction->handle(
                expertPanel: $expertPanel,
                subject: $subject,
                body: $body,
                attachments: $attachments,
                ccAddresses: $ccAddresses
            );
        }
    }
}
