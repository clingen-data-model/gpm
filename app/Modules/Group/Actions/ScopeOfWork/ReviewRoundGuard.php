<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Illuminate\Validation\ValidationException;

class ReviewRoundGuard
{
    // Call inside a transaction, before changing either record.
    public function handle(Submission $submission): ?ScopeOfWorkVersion
    {
        $submission->refresh();
        if (!$submission->scope_of_work_version_id
            && data_get($submission->data, 'context') !== 'scope_of_work_revision') {
            return null;
        }

        $revision = ScopeOfWorkVersion::whereKey($submission->scope_of_work_version_id)
            ->lockForUpdate()->first();
        $current = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
        $submission->setRawAttributes($current->getAttributes(), true);
        $submission->unsetRelations();

        if (!$revision
            || data_get($submission->data, 'context') !== 'scope_of_work_revision'
            || (int) $submission->scope_of_work_version_id !== $revision->id
            || $revision->group_id !== $submission->group_id
            || $revision->submission_id !== $submission->id
            || $revision->status !== ScopeOfWorkVersion::STATUS_SUBMITTED
            || $revision->approved_at !== null
            || !$submission->is_pending
            || $submission->closed_at !== null) {
            throw ValidationException::withMessages([
                'submission' => 'Only the current active submitted Scope of Work review round can be reviewed.',
            ]);
        }

        return $revision;
    }
}
