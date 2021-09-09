<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use App\Modules\Group\Events\MemberRoleAssigned;

class MemberAssignRole
{
    use AsController;
    use AsObject;

    public function handle(GroupMember $groupMember, $roles)
    {
        $roles = collect($roles)
            ->map(function ($role) {
                if (is_int($role)) {
                    $role = config('permission.models.role')::findOrFail($role);
                }
                return $role;
            });
        $roles->each(function ($role) {
            if ($role->scope !== 'group') {
                throw ValidationException::withMessages([
                    'role_ids' => ['All roles must be group roles.']
                ]);
            }
        });

        $groupMember->assignRole($roles);

        Event::dispatch(new MemberRoleAssigned($groupMember, $roles));

        return $groupMember;
    }

    public function asController($groupUuid, $memberId, ActionRequest $request)
    {
        return $this->handle(GroupMember::findOrFail($memberId), $request->role_ids);
    }

    public function rules(): array
    {
        return [
            'role_ids' => 'required|array|exists:roles,id'
        ];
    }
}
