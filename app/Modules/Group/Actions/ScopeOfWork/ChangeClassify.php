<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Services\ScopeOfWorkRuleResolver;
use Lorisleiva\Actions\Concerns\AsObject;

class ChangeClassify
{
    use AsObject;

    public function __construct(
        private ScopeOfWorkRuleResolver $ruleResolver
    ) {
    }

    public function handle(Group $group, string $ruleKey): array
    {
        $groupType = $this->getGroupType($group);

        return $this->ruleResolver->resolve($ruleKey, $groupType);
    }

    private function getGroupType(Group $group): string
    {
        $group->loadMissing('type');

        return $group->type->name;
    }
}