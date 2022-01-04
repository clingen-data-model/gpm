<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\GroupMember;
use App\Modules\Groups\Events\PublishableApplicationEvent;

abstract class GroupMemberEvent extends GroupEvent implements PublishableApplicationEvent
{
    public function __construct(public GroupMember $groupMember)
    {
        $this->group = $groupMember->group;
    }
}
