<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsObject;

class ScopeOfWorkRevisionRequestRevisionsFromSubmission
{
    use AsObject;

    public function handle(Submission $submission): ?ScopeOfWorkVersion
    {
        $revision = ScopeOfWorkVersion::where('submission_id', $submission->id)
            ->where('status', ScopeOfWorkVersion::STATUS_SUBMITTED)
            ->first();

        if (!$revision) {
            return null;
        }

        $revision->update([
            'status' => ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED,
        ]);

        return $revision->fresh(['changes', 'latestSnapshot', 'baseVersion', 'submission']);
    }
}