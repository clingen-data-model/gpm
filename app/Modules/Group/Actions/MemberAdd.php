<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Notifications\AddedToGroupNotification;

class MemberAdd
{
    use AsController;
    use AsObject;

    private $sendNotification = true;

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

        if ($this->sendNotification) {
            Notification::send($person, new AddedToGroupNotification($group));
        }

        return $groupMember;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $person = Person::findOrFail($request->person_id);
        $roles = config('permission.models.role')::find($request->role_ids);

        $member = $this->handle(group: $group, person: $person, data: ['is_contact' => $request->is_contact]);

        if ($roles->count() > 0) {
            $member = $this->assignRoleAction->handle($member, $roles);
        }

        $member->load('cois', 'person', 'group');

        return new MemberResource($member);
    }

    public function authorize(ActionRequest $request): bool
    {
        $user = $request->user();
        if (!$user) { return false;}

        $group = $request->route('group'); // adjust param name if different        
        $group->load('expertPanel');

        // Step 1 approved → only super-admin can add genes    
        if ($group->expertPanel && $group->expertPanel->getApprovalDateForStep(1)) {
            return $user->hasRole('super-admin');
        }

        // Otherwise fall back to existing permission logic
        return $user->can('inviteMembers', $group);
    }

    public function cancelNotification(): static
    {
        $this->sendNotification = false;
        return $this;
    }

    public function sendNotification(): static
    {
        $this->sendNotification = true;
        return $this;
    }


    public function rules(): array
    {
        return [
            'person_id' => 'required|exists:people,id'
        ];
    }
}
