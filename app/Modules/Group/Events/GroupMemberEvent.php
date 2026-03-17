<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;
use App\Modules\Group\Models\GroupMember;

abstract class GroupMemberEvent extends GroupEvent
{
    use IsPublishableGroupEvent;

    public function __construct(public GroupMember $groupMember)
    {
        $this->group = $groupMember->group;
    }

    public function getProperties(): ?array
    {
        $this->groupMember->loadMissing([
            'person.latestCocAttestation',
        ]);

        return ['members' => [$this->mapMemberForMessage($this->groupMember, false)]];
    }

}
