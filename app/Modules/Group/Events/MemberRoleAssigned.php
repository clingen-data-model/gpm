<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MemberRoleAssigned extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GroupMember $groupMember, public Collection $roles)
    {
        parent::__construct($groupMember);
    }

    public function getLogEntry(): string
    {
        return $this->groupMember->name . ' given roles ' . $this->roles->pluck('name')->join(',', ', and ');
    }

    public function getProperties(): ?array
    {
        $props = parent::getProperties();
        $props['roles'] = $this->roles->pluck('name')->toArray();
        return $props;
    }

}
