<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;
use App\Modules\Group\Models\GroupMember;
use App\Services\CocService;

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
        $cocService = app(CocService::class);
        
        return ['members' => [$this->mapMemberForMessage($this->groupMember, false, $cocService)]];
    }

}
