<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Submission;
use App\Modules\Group\Actions\ScopeOfWork\RevisionApproveFromSubmission;

class SubmissionApprove
{
    public function __construct(
        private RevisionApproveFromSubmission $approveScopeOfWorkRevision
    ) {
    }

    public function handle(Submission $submission, $dateApproved): Submission
    {
        if ($this->approveScopeOfWorkRevision->handle($submission, \Carbon\Carbon::parse($dateApproved))) {
            return $submission->refresh();
        }

        $submission->update([
            'submission_status_id' => config('submissions.statuses.approved.id'),
            'closed_at' => $dateApproved
        ]);
        return $submission;
    }
}
