<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Submission;

class SubmissionApprove
{
    public function __construct(
        private ScopeOfWorkRevisionApproveFromSubmission $approveScopeOfWorkRevision
    ) {
    }

    public function handle(Submission $submission, $dateApproved): Submission
    {
        $submission->update([
            'submission_status_id' => config('submissions.statuses.approved.id'),
            'closed_at' => $dateApproved
        ]);
        $this->approveScopeOfWorkRevision->handle($submission, $dateApproved);
        return $submission;
    }
}
