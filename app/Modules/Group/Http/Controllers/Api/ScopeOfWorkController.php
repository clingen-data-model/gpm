<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Actions\ScopeOfWork\HistoryGet;
use App\Modules\Group\Actions\ScopeOfWork\StatusGet;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Actions\ScopeOfWork\VersionCompareGet;
use App\Modules\Group\Models\ScopeOfWorkVersion;

class ScopeOfWorkController extends Controller
{
    public function status(Group $group)
    {
        return StatusGet::run($group);
    }

    public function history(Group $group)
    {
        return HistoryGet::run($group);
    }
    
    public function compare(
        Group $group,
        string $fromVersionUuid,
        string $toVersionUuid
    ) {
        $fromVersion = ScopeOfWorkVersion::where('uuid', $fromVersionUuid)->firstOrFail();
        $toVersion = ScopeOfWorkVersion::where('uuid', $toVersionUuid)->firstOrFail();

        return VersionCompareGet::run($group, $fromVersion, $toVersion);
    }
}