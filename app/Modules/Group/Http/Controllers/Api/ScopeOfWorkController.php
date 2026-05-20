<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Actions\ScopeOfWorkHistoryGet;
use App\Modules\Group\Actions\ScopeOfWorkStatusGet;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Actions\ScopeOfWorkVersionCompareGet;
use App\Modules\Group\Models\ScopeOfWorkVersion;

class ScopeOfWorkController extends Controller
{
    public function status(Group $group)
    {
        return ScopeOfWorkStatusGet::run($group);
    }

    public function history(Group $group)
    {
        return ScopeOfWorkHistoryGet::run($group);
    }
    
    public function compare(
        Group $group,
        string $fromVersionUuid,
        string $toVersionUuid
    ) {
        $fromVersion = ScopeOfWorkVersion::where('uuid', $fromVersionUuid)->firstOrFail();
        $toVersion = ScopeOfWorkVersion::where('uuid', $toVersionUuid)->firstOrFail();

        return ScopeOfWorkVersionCompareGet::run($group, $fromVersion, $toVersion);
    }
}