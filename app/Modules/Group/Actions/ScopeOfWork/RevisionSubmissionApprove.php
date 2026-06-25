<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Actions\SubmissionApprove;
use App\Modules\Group\Events\ScopeOfWorkReviewCompleted;

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

        $approvedSubmission = DB::transaction(function () use ($submission, $dateApproved) {
            return $this->approveSubmission->handle($submission, $dateApproved);            
        });
        $approvedSubmission->load('scopeOfWorkVersion');
        event(new ScopeOfWorkReviewCompleted(submission: $approvedSubmission, revision: $approvedSubmission->scopeOfWorkVersion, outcome: 'approved'));
        return $approvedSubmission;
    }

    public function asController(ActionRequest $request,Group $group,Submission $submission): Submission {
        return $this->handle(
            group: $group,
            submission: $submission,
            dateApproved: Carbon::parse($request->date_approved),
        );
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('ep-applications-manage');
    }

    public function rules(): array
    {
        return [
            'date_approved' => ['required', 'date'],
        ];
    }
}