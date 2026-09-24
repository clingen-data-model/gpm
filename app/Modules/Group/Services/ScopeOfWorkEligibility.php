<?php

namespace App\Modules\Group\Services;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\Group;
use Illuminate\Validation\ValidationException;

class ScopeOfWorkEligibility
{
    public static function applies(Group $group): bool
    {
        return $group->expertPanel()->whereNotNull('date_completed')->exists();
    }

    public static function requireCompleted(Group $group): ExpertPanel
    {
        // Query persisted state, never trust a previously loaded relationship.
        $panel = $group->expertPanel()->first();
        if (!$panel || $panel->date_completed === null) {
            throw ValidationException::withMessages([
                'scope_of_work' => 'Scope of Work versioning requires a completed initial application.',
            ]);
        }
        return $panel;
    }
}
