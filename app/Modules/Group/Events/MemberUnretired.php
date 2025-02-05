<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MemberUnretired extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name . ' "un-retired" from ' . $this->groupMember->group->displayName;
    }

    public function getSubject(): Group
    {
        return $this->groupMember->group;
    }

    public function getProperties(): array
    {
        return [
            'group_member' => $this->groupMember,
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
            'group' => $this->groupMember->group
        ];
    }


    public function getEventType(): string
    {
        return 'member_unretired';
    }

}
