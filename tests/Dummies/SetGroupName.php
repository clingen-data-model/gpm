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
    
}