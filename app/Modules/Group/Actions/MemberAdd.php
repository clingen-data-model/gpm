<?php

namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Models\Role as ModelsRole;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Spatie\Permission\Contracts\Role;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Http\Resources\MemberResource;

class MemberAdd
{
    use AsController;
    use AsObject;

    public function __construct(private MemberAssignRole $assignRoleAction)
    {
    }
    

    public function handle(Group $group, Person $person, ?array $data = []): GroupMember
    {
        $memberData = array_merge([
            'group_id' => $group->id,
            'person_id' => $person->id
        ], $data);

        $groupMember = GroupMember::create($memberData);

        Event::dispatch(new MemberAdded($groupMember));

        return $groupMember;
    }

    public function asController(ActionRequest $request, string $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $person = Person::findOrFail($request->person_id);
        $roles = config('permission.models.role')::find($request->role_ids);

        $member = $this->handle(group: $group, person: $person, data: ['is_contact' => $request->is_contact]);

        if ($roles->count() > 0) {
            $member = $this->assignRoleAction->handle($member, $roles);
        }

        $member->load('cois');

        return new MemberResource($member);
    }

    public function rules(): array
    {
        return [
            'person_id' => 'required|exists:people,id'
        ];
    }
}
