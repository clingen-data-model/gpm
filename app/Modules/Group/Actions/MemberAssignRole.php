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
use App\Modules\Group\Http\Resources\MemberResource;
use App\Services\CoreMemberAttestation;

class MemberAssignRole
{
    use AsController;
    use AsObject;

    public function __construct(private CoreMemberAttestation $attestation) {}

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
        $this->attestation->handle($groupMember, $roles);
        Event::dispatch(new MemberRoleAssigned($groupMember, $roles));

        return $groupMember;
    }

    public function asController(ActionRequest $request, $groupUuid, $memberId)
    {
        $groupMember = $this->handle(GroupMember::findOrFail($memberId), $request->role_ids);
        $groupMember->load('cois', 'permissions', 'roles');
        return new MemberResource($groupMember);
    }

    public function rules(): array
    {
        return [
            'role_ids' => 'required|array|exists:roles,id'
        ];
    }
}
