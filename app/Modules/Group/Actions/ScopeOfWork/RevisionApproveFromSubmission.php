<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Events\ScopeOfWorkReviewCompleted;
use Lorisleiva\Actions\Concerns\AsObject;

class RevisionApproveFromSubmission
{
    use AsObject;

    public function handle(Submission $submission, Carbon $dateApproved, ?int $approvedBy = null): ?ScopeOfWorkVersion
    {
        return DB::transaction(function () use ($submission, $dateApproved, $approvedBy) {
            $revision = app(ReviewRoundGuard::class)->handle($submission);
            if (!$revision) {
                return null;
            }

            $revision->update([
                'status' => ScopeOfWorkVersion::STATUS_APPROVED,
                'approved_by' => $approvedBy ?? Auth::id(),
                'approved_at' => $dateApproved,
            ]);
            $submission->update([
                'submission_status_id' => config('submissions.statuses.approved.id'),
                'closed_at' => $dateApproved,
            ]);
            $revision = $revision->fresh(['changes', 'latestSnapshot', 'baseVersion', 'submission']);
            event(new ScopeOfWorkReviewCompleted(
                submission: $submission->fresh(), revision: $revision, outcome: 'approved'
            ));

            return $revision;
        });
    }
}
