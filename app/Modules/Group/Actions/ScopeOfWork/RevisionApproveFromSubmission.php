<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsObject;

class RevisionApproveFromSubmission
{
    use AsObject;

    public function handle(Submission $submission, Carbon $dateApproved): ?ScopeOfWorkVersion
    {
        $revision = ScopeOfWorkVersion::where('submission_id', $submission->id)
            ->where('status', ScopeOfWorkVersion::STATUS_SUBMITTED)
            ->first();

        if (!$revision) {
            return null;
        }

        $revision->update([
            'status' => ScopeOfWorkVersion::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => $dateApproved,
        ]);

        return $revision->fresh(['changes', 'latestSnapshot', 'baseVersion', 'submission']);
    }
}