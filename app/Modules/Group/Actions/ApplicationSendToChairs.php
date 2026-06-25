<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\Group\Events\ApplicationSentToChairs;
use App\Modules\Group\Notifications\ApplicationReadyForApproverReview;
use App\Modules\Group\Models\Submission;
use Illuminate\Validation\ValidationException;

class ApplicationSendToChairs
{
    public function __construct(private NextActionCreate $createNextAction)
    {
    }


    public function handle(
        Group $group,
        ?string $additionalComments = null,
        ?int $submissionId = null
    ): Group {
        $submission = $submissionId ? $group->submissions()->findOrFail($submissionId) : $group->latestPendingSubmission;

        if (! $submission) {
            throw ValidationException::withMessages(['submission' => 'No pending submission was found.']);
        }

        if ( (int) $submission->submission_status_id !== (int) config('submissions.statuses.pending.id') ) {
            throw ValidationException::withMessages([
                'submission' => 'Only a pending submission can be sent to the chairs.',
            ]);
        }

        DB::transaction(function () use ($group, $submission, $additionalComments) {
            $submission->update([
                'submission_status_id' => config('submissions.statuses.under-chair-review.id'),
                'sent_to_chairs_at' => Carbon::now(),
                'notes_for_chairs' => $additionalComments,
            ]);
            $this->createNextActionForChairs($group, $submission);
        });
        $this->notifyChairs($group);
        event(new ApplicationSentToChairs( group: $group, comments: $group->pendingComments, additionalComments: $additionalComments ));

        return $group->fresh();
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('ep-applications-manage');
    }

    private function notifyChairs(Group $group): void
    {
        $chairs = User::permission('ep-applications-approve')
                    ->with('person')
                    ->get()
                    ->pluck('person');
        Notification::send($chairs, new ApplicationReadyForApproverReview($group));
    }

    private function createNextActionForChairs(
        Group $group,
        Submission $submission
    ): void {
        $data = $submission->data ?? [];
        $isScopeOfWorkRevision = ($data['context'] ?? null) === 'scope_of_work_revision';
        $approvalStep = $data['approval_step'] ?? $group->expertPanel->current_step;
        $entry = $isScopeOfWorkRevision ? 'Review Scope of Work revision ' .($data['target_version'] ?? '').'.' : 'Review Step '.$approvalStep.' application.';
        $this->createNextAction->handle(
            expertPanel: $group->expertPanel,
            entry: $entry,
            dateCreated: Carbon::now()->format('Y-m-d H:i:s'),
            step: $approvalStep,
            assignedTo: config('next_actions.assignees.chairs.id'),
            typeId: config('next_actions.types.chair-review.id'),
        );
    }
}
