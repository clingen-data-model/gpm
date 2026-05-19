<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;

class ScopeOfWorkRevisionRefreshRun
{
    use AsController;

    public function handle(Group $group): array
    {
        ScopeOfWorkRevisionRefresh::run($group, Auth::user());

        return ScopeOfWorkStatusGet::run($group);
    }

    public function asController(Group $group)
    {
        return $this->handle($group);
    }
}