<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Spatie\Permission\Contracts\Role;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MemberRoleRemoved extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;
    public function __construct(public GroupMember $groupMember, public Role $role)
    {
        parent::__construct($groupMember);
    }

    public function getLogEntry(): string
    {
        return 'Removed role '.$this->role->name.' from member '.$this->groupMember->person->name.'.';
    }

    public function getProperties(): ?array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'role' => $this->role->only('id', 'name'),
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
        ];
    }

    public function getEventType(): string
    {
        return 'member_role_removed';
    }

}
