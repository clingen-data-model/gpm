<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\Concerns\AsController;

class ScopeOfWorkHistoryShow
{
    use AsController;

    public function handle(Group $group): array
    {
        return ScopeOfWorkHistoryGet::run($group);
    }

    public function asController(Group $group)
    {
        return $this->handle($group);
    }
}