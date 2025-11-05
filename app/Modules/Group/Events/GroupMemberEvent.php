<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;
use App\Modules\Group\Models\GroupMember;

abstract class GroupMemberEvent extends GroupEvent
{
    use IsPublishableApplicationEvent;

    public function __construct(public GroupMember $groupMember)
    {
        $this->group = $groupMember->group;
    }

    public function getProperties(): ?array
    {
        $member = $this->groupMember;
        return ['member' => $this->mapMemberForMessage($member)];
    }

}
