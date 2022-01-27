<?php

namespace App\Modules\User\Actions;

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class RolesAndPermissionsUpdate
{
    use AsController;

    public function handle(User $user, array $roleIds, array $permissionIds): User
    {
        $user->syncRoles($roleIds);
        $user->syncPermissions($permissionIds);
        return $user;
    }

    public function asController(ActionRequest $request, User $user)
    {
        $updatedUser = $this->handle(
            user: $user,
            roleIds: ($request->role_ids ?? []),
            permissionIds: ($request->permission_ids ?? [])
        );
        $updatedUser->load('person', 'roles', 'permissions', 'roles.permissions');
        return $updatedUser;
    }

    public function rules(): array
    {
        return [
           'role_ids' => 'nullable|array',
           'permission_ids' => 'nullable|array'
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        // return Auth::user()->can('updateRolesAndPermissions', $request->user);
        return Auth::user()->hasPermissionTo('users-manage');
    }
}
