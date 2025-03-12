<?php

namespace App\Modules\Group\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupMember;
use Spatie\Permission\Contracts\Permission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MemberPermissionRevoked extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GroupMember $groupMember, public Permission $permission)
    {
        parent::__construct($groupMember);
    }

    public function getSubject(): Model
    {
        return $this->groupMember->group;
    }

    public function getLogEntry(): string
    {
        return 'Permission ' . $this->permission->name . ' revoked from member ' . $this->groupMember->person->name . '.';
    }

    public function getProperties(): array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'permission' => $this->permission->only('id', 'name'),
            'person' => $this->groupMember->person->only('id', 'name', 'email')
        ];
    }

}
