<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\GroupMember;

class MemberAssignRole
{
    public function handle(GroupMember $groupMember, $roles)
    {
        $groupMember->assignRole($roles);
    }
}
