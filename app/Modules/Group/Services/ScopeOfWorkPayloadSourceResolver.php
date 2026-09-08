<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;

class ScopeOfWorkPayloadSourceResolver
{
    /** Null means DX should retain its existing live payload. */
    public function resolve(Group $group): ?array
    {
        if (! ScopeOfWorkVersion::groupHasActiveRevision($group)) {
            return null;
        }

        $approved = ScopeOfWorkVersion::latestApprovedForGroup($group);
        $snapshot = $approved?->latestSnapshot?->snapshot;

        return is_array($snapshot) ? $snapshot : null;
    }
}
