<?php

namespace Tests\Dummies;

use App\Modules\Group\Models\Group;

class SetGroupName 
{
    public function handle(Group $group, string $name): Group
    {
        $group->name = $name;

        return $group;
    }
    
    public function __invoke(Group $group, string $name): Group
    {
        return $this->handle($group, $name);
    }
}