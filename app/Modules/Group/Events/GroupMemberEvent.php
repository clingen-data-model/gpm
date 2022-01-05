<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\GroupMember;

abstract class GroupMemberEvent extends GroupEvent
{
    public function __construct(public GroupMember $groupMember)
    {
        $this->group = $groupMember->group;
    }
}
