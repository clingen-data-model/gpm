<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

class ScopeOfWorkRevisionGuard
{
    use AsObject;

    public function ensureNotUnderReview(Group $group): void
    {
        $submittedRevisionExists = ScopeOfWorkVersion::forGroup($group)
            ->where('status', ScopeOfWorkVersion::STATUS_SUBMITTED)
            ->exists();

        if ($submittedRevisionExists) {
            throw ValidationException::withMessages([
                'scope_of_work' => 'Scope of Work changes are currently under review. Please wait until the revision is approved or revisions are requested.',
            ]);
        }
    }
}