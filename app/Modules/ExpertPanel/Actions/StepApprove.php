<?php

namespace App\Modules\ExpertPanel\Actions;

use InvalidArgumentException;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Group\Actions\SubmissionApprove;
use App\Notifications\ValueObjects\MailAttachment;
use App\Modules\ExpertPanel\Actions\NotifyContacts;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Actions\ApplicationComplete;
use App\Mail\UserDefinedMailTemplates\InitialApprovalMailTemplate;
use App\Mail\UserDefinedMailTemplates\SpecificationDraftMailTemplate;
use App\Mail\UserDefinedMailTemplates\SpecificationPilotMailTemplate;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;
use App\Mail\UserDefinedMailTemplates\SustainedCurationApprovalMailTemplate;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;
use App\Modules\ExpertPanel\Actions\AffiliationUpdate;
use Illuminate\Support\Facades\DB;

class StepApprove
{
    use AsAction;

    public function __construct(
        private NotifyContacts $notifyContactsAction,
        private ApplicationComplete $applicationCompleteAction,
        private SubmissionApprove $approveSubmission,
        private StepManagerFactory $stepManagerFactory,
        private AffiliationUpdate $affiliationUpdate
    ) {
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(
        ExpertPanel $expertPanel,
        $dateApproved,
        bool $notifyContacts = false,
        ?string $subject = null,
        ?string $body = null,
        $attachments = []
    ) {
        $stepManager = ($this->stepManagerFactory)($expertPanel);
        $dateApproved = $dateApproved ? Carbon::parse($dateApproved) : Carbon::now();

        if (! $stepManager->canApprove()) {
            throw new UnmetStepRequirementsException($expertPanel, $stepManager->getUnmetRequirements());
        }

        DB::transaction(function () use (&$expertPanel, $stepManager, $dateApproved, $notifyContacts, $subject, $body, $attachments) {        
            $this->setStepApprovalDate($expertPanel, $dateApproved);
            $approvedStep = (int) $expertPanel->current_step;

            if (! $stepManager->isLastStep()) {
                $expertPanel->current_step++;
            }
            $expertPanel->save();

            if (($approvedStep === 1 && $expertPanel->is_gcep) ||
                ($approvedStep === 4 && $expertPanel->is_vcep)) {
                $this->affiliationUpdate->activate($expertPanel);
            }

            $submission = $this->getSubmission($expertPanel, $approvedStep);
            if ($submission) {
                $this->approveSubmission->handle($submission, $dateApproved);
            }

            DB::afterCommit(function () use ($expertPanel, $approvedStep, $dateApproved, $stepManager, $notifyContacts, $subject, $body, $attachments) {
                event(new StepApproved(
                    application: $expertPanel,
                    step: $approvedStep,
                    dateApproved: $dateApproved
                ));

                if ($stepManager->isLastStep()) {
                    $this->applicationCompleteAction->handle($expertPanel, $dateApproved);
                }

                if ($notifyContacts) {
                    $this->notifyContacts($expertPanel, $approvedStep, $subject, $body, $attachments);
                }
            });
        });
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $expertPanel = $group->expertPanel;
        try {
            $attachments = collect($request->attachments)
                ->map(function ($file) {
                    return MailAttachment::createFromUploadedFile($file);
                })
                ->toArray();

            $this->handle(
                $expertPanel,
                dateApproved: $request->date_approved ? Carbon::parse($request->date_approved) : Carbon::now(),
                notifyContacts: ($request->has('notify_contacts')) ? $request->notify_contacts : false,
                subject: $request->subject,
                body: $request->body,
                attachments: $attachments
            );
            $ep = $expertPanel->fresh();
            return $ep;
        } catch (UnmetStepRequirementsException $e) {
            return response([
                'message' => $e->getMessage(),
                'errors' => $e->getUnmetRequirements(),
            ], 422);
        }
    }

    public function rules()
    {
        return [
            'date_approved' => 'required|date',
            'notify_contacts' => 'nullable'
        ];
    }

    public function prepareForValidation(ActionRequest $request)
    {
        $request->merge([
            'notify_contacts' => filter_var($request->notify_contacts, FILTER_VALIDATE_BOOL),
        ]);
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


    private function notifyContacts($expertPanel, $approvedStep, $subject, $body, $attachments)
    {
        if ($expertPanel->contacts->count() == 0) {
            if (!app()->environment('testing')) {
                Log::error('Tried to send a step approval notifications for group '.$expertPanel->display_name.' ('.$expertPanel->group_id.') that has no contacts.  This is a data entry issue and not a code defect.');
            }
            return;
        }

        $defaultMail = $this->makeMailTemplate($expertPanel, $approvedStep);


        $this->notifyContactsAction->handle(
            expertPanel: $expertPanel,
            subject: $subject ?? $defaultMail->renderSubject(),
            body: $body ?? $defaultMail->renderBody(),
            attachments: $attachments,
            ccAddresses: $defaultMail->getCc()
        );

        Notification::send(
            $expertPanel->contacts->pluck('person'),
            new ApplicationStepApprovedNotification($expertPanel, $approvedStep)
        );
    }

    private function makeMailTemplate(ExpertPanel $expertPanel, int $approvedStep)
    {
        switch ($approvedStep) {
            case 1:
                return new InitialApprovalMailTemplate($expertPanel->group);
            case 2:
                return new SpecificationDraftMailTemplate($expertPanel->group);
            case 3:
                return new SpecificationPilotMailTemplate($expertPanel->group);
            case 4:
                return new SustainedCurationApprovalMailTemplate($expertPanel->group);
            default:
                throw new InvalidArgumentException('Unexpected approvedStep recieved: '.$approvedStep.'.  1-4 expected.');
        }
    }

    private function setStepApprovalDate(ExpertPanel $expertPanel, Carbon $dateApproved)
    {
        $expertPanel->{'step_'.$expertPanel->current_step.'_approval_date'} = $dateApproved;
        return $expertPanel;
    }

    private function handleSubmission($expertPanel, $approvedStep, $dateApproved)
    {
        $submission = $this->getSubmission($expertPanel, $approvedStep);
        if ($submission) {
            $this->approveSubmission->handle($submission, $dateApproved);
            return;
        }

    }
}
