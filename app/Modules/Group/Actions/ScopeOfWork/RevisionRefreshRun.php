<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;

class RevisionRefreshRun
{
    use AsController;

    public function handle(Group $group): array
    {
        RevisionRefresh::run($group, Auth::user());

        return StatusGet::run($group);
    }

    public function asController(Group $group)
    {
        return $this->handle($group);
    }
}