<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Submission;
use App\Modules\Group\Events\SubmissionApproved;

class SubmissionApprove
{
    public function handle(Submission $submission, $dateApproved): Submission
    {
        $submission->update([
            'submission_status_id' => config('submissions.statuses.approved.id'),
            'closed_at' => $dateApproved
        ]);

        return $submission;
    }
}
