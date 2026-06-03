<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkSnapshot;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Lorisleiva\Actions\Concerns\AsObject;

class SnapshotCreate
{
    use AsObject;

    public function handle(
        ScopeOfWorkVersion $version,
        ?Group $group = null,
        ?array $snapshot = null
    ): ScopeOfWorkSnapshot {
        $group = $group ?: $version->group;
        $snapshot = $snapshot ?: SnapshotBuild::run($group);

        return ScopeOfWorkSnapshot::create([
            'scope_of_work_version_id' => $version->id,
            'snapshot_schema_version' => $snapshot['schema_version'],
            'snapshot' => $snapshot,
        ]);
    }
}