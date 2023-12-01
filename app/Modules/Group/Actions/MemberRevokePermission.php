<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Events\MemberPermissionRevoked;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\Permission\Contracts\Permission;

class MemberRevokePermission
{
    use AsObject;
    use AsController;

    public function handle(GroupMember $groupMember, Permission $permission): GroupMember
    {
        if ($groupMember->hasPermissionTo($permission)) {
            $groupMember->revokePermissionTo($permission);
        }

        Event::dispatch(new MemberPermissionRevoked($groupMember, $permission));

        return $groupMember;
    }

    public function asController(Request $request, $groupUuid, $groupMemberId, $permissionId)
    {
        $groupMember = Group::findByUuidOrFail($groupUuid)
                        ->members()->findOrFail($groupMemberId);

        $permission = config('permission.models.permission')::find($permissionId);

        $groupMember = $this->handle($groupMember, $permission);
        $groupMember->load('cois', 'permissions', 'roles');

        return new MemberResource($groupMember);
    }
}
