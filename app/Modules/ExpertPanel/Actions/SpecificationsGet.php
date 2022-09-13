<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsController;

class SpecificationsGet
{
    use AsController;

    public function handle(Group $group): Collection
    {
        return $group->expertPanel->specifications()->with('rulesets')->get();
    }

    public function asController(Group $group)
    {
        if (!$group->isVcep) {
            return response(['message' => 'Only VCEPs have specifications.'], 404);
        }

        return $this->handle($group);

    }
}
