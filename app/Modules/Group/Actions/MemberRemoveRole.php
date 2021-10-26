<?php

namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Contracts\Role;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\MemberRoleRemoved;
use App\Modules\Group\Http\Resources\MemberResource;

class MemberRemoveRole
{
    use AsController;
    use AsObject;

    public function handle(GroupMember $groupMember, Role $role): GroupMember
    {
        $groupMember->removeRole($role);

        Event::dispatch(new MemberRoleRemoved($groupMember, $role));
        return $groupMember;
    }

    public function asController(Request $request, string $groupUuid, int $memberId, int $roleId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $groupMember = $group->members()->findOrFail($memberId);
        $role = config('permission.models.role')::findOrFail($roleId);
        
        $groupMember = $this->handle($groupMember, $role);
        $groupMember->load('cois', 'permissions', 'roles');


        return new MemberResource($groupMember);
    }
}
