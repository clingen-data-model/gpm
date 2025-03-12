<?php

namespace App\Modules\Group\Actions;

use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\Permission\Contracts\Permission;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Events\MemberPermissionGranted;

class MemberGrantPermissions
{
    use AsObject;
    use AsController;

    public function handle(GroupMember $groupMember, Collection $permissions): GroupMember
    {
        $permissions->each(function ($perm) {
            if ($perm->scope !== 'group') {
                throw ValidationException::withMessages([
                    'permission_ids' => ['All permissions must be group permissions.']
                ]);
            }
        });

        $groupMember->givePermissionTo($permissions);

        Event::dispatch(new MemberPermissionGranted($groupMember, $permissions));

        return $groupMember;
    }

    public function asController(ActionRequest $request, $groupUuid, $memberId)
    {
        $groupMember = Group::findByUuidOrFail($groupUuid)->members()->findOrFail($memberId);
        $permissions = config('permission.models.permission')::find($request->permission_ids);

        $member = $this->handle($groupMember, $permissions);
        $member->load('cois', 'permissions', 'roles');
        
        return new MemberResource($member);
    }

    public function rules(): array
    {
        return [
            'permission_ids' => 'required|array|exists:permissions,id'
        ];
    }
}
