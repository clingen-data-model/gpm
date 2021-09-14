<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\Permission\Contracts\Permission;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\MemberPermissionRevoked;

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
    
    public function asController($groupUuid, $groupMemberId, $permissionId)
    {
        $groupMember = Group::findByUuidOrFail($groupUuid)
                        ->members()->findOrFail($groupMemberId);

        $permission = config('permission.models.permission')::find($permissionId);

        return $this->handle($groupMember, $permission);
    }
    
    
}
