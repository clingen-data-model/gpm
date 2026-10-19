<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Actions\SubmissionApprove;
use App\Modules\ExpertPanel\Actions\NotifyContacts;
use App\Mail\UserDefinedMailTemplates\InitialApprovalMailTemplate;
use App\Mail\UserDefinedMailTemplates\SustainedCurationApprovalMailTemplate;
use App\Notifications\ValueObjects\MailAttachment;
use Illuminate\Support\Facades\DB;

class RevisionSubmissionApprove
{
    use AsAction;

    public function __construct(private SubmissionApprove $approveSubmission) {
    }

    public function handle(Group $group, Submission $submission,Carbon $dateApproved): Submission {
        if ((int) $submission->group_id !== (int) $group->id) {
            abort(404);
        }

        if (data_get($submission->data, 'context') !== 'scope_of_work_revision') {
            throw ValidationException::withMessages([
                'submission' => 'This is not a Scope of Work revision submission.',
            ]);
        }

        if (! $submission->scope_of_work_version_id) {
            throw ValidationException::withMessages([
                'submission' => 'The submission is not linked to a Scope of Work version.',
            ]);
        }

        $allowedStatusIds = [(int) config('submissions.statuses.pending.id'), (int) config('submissions.statuses.under-chair-review.id')];
        if (! in_array((int) $submission->submission_status_id, $allowedStatusIds, true)) 
        {
            throw ValidationException::withMessages([
                'submission' => ['Only an active Scope of Work submission can be approved.'],
            ]);
        }

        return $this->approveSubmission->handle($submission, $dateApproved);
    }

    public function asController(ActionRequest $request,Group $group,Submission $submission): Submission {
        return DB::transaction(function () use ($request, $group, $submission) {
            $approved = $this->handle($group, $submission, Carbon::parse($request->date_approved));
            if ($request->boolean('notify_contacts')) {
                $step = (int) data_get($submission->data, 'approval_step', 1);
                $template = $step === 4 ? new SustainedCurationApprovalMailTemplate($group) : new InitialApprovalMailTemplate($group);
                app(NotifyContacts::class)->handle(
                    expertPanel: $group->expertPanel,
                    subject: $request->subject ?? $template->renderSubject(),
                    body: $request->body ?? $template->renderBody(),
                    attachments: collect($request->file('attachments', []))->map(fn ($file) => MailAttachment::createFromUploadedFile($file))->all(),
                    ccAddresses: $template->getCC(),
                    step: $step,
                );
            }
            return $approved;
        });
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('ep-applications-manage');
    }

    public function rules(): array
    {
        return [
            'date_approved' => ['required', 'date'],
            'notify_contacts' => ['sometimes', 'boolean'],
            'subject' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file'],
        ];
    }
}
