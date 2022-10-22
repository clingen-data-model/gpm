<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
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
use App\Mail\UserDefinedMailTemplates\SustainedCurationApprovalMailTemplate;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;
use InvalidArgumentException;

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
        ExpertPanel $expertPanel,
        $dateApproved,
        bool $notifyContacts = false,
        ?string $subject = null,
        ?string $body = null,
        $attachments = []
    ) {
        dump('fuck me 1');
        $stepManager = app()->make(StepManagerFactory::class)($expertPanel);
        dump('fuck me 2');
        $dateApproved = $dateApproved ? Carbon::parse($dateApproved) : Carbon::now();
        dump('fuck me 3');

        if (! $stepManager->canApprove()) {
            throw new UnmetStepRequirementsException($expertPanel, $stepManager->getUnmetRequirements());
        }
        dump('fuck me 4');

        $expertPanel->{'step_'.$expertPanel->current_step.'_approval_date'} = $dateApproved;
        dump('fuck me 5');
        $approvedStep = $expertPanel->current_step;
        dump('fuck me 6');

        if (!$stepManager->isLastStep()) {
            $expertPanel->current_step++;
        }
        dump('fuck me 7');
        $expertPanel->save();
        dump('fuck me 8');

        $submission = $this->getSubmission($expertPanel, $approvedStep);
        dump('fuck me 9');
        if ($submission) {
            $this->approveSubmission->handle($submission, $dateApproved);
            dump('fuck me 10');
        }

        event(new StepApproved(
            application: $expertPanel,
            step: $approvedStep,
            dateApproved: $dateApproved
        ));
        dump('fuck me 11');

        if ($stepManager->isLastStep()) {
            $this->applicationCompleteAction->handle($expertPanel, $dateApproved);
        }
        dump('fuck me 12');

        if ($notifyContacts) {
            $this->notifyContacts($expertPanel, $approvedStep, $subject, $body, $attachments);
        }
        dump('fuck me 13');
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
            dump('fuck me 14');
            $ep = $expertPanel->fresh();
            dump('fuck me 15');
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
                \Log::error('Tried to send a step approval notifications for group '.$expertPanel->display_name.' ('.$expertPanel->group_id.') that has no contacts.  This is a data entry issue and not a code defect.');
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

}
