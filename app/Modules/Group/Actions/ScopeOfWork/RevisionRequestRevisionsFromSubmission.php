<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsObject;

class RevisionRequestRevisionsFromSubmission
{
    use AsObject;

    public function handle(Submission $submission): ?ScopeOfWorkVersion
    {
        $revision = app(ReviewRoundGuard::class)->handle($submission);
        if (!$revision) {
            return null;
        }

        $revision->update([
            'status' => ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED,
        ]);

        return $revision->fresh(['changes', 'latestSnapshot', 'baseVersion', 'submission']);
    }
}