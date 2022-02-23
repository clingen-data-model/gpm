<?php

namespace App\Modules\Group\Actions;

use Ramsey\Uuid\Uuid;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Invite;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Actions\MemberAdd;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Group\Events\MemberInvited;
use App\Modules\Person\Actions\PersonCreate;
use App\Modules\Person\Actions\PersonInvite;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Http\Resources\MemberResource;

class MemberInvite
{
    use AsController;
    use AsObject;
    
    public function __construct(
        private PersonCreate $createPerson,
        private PersonInvite $invitePerson,
        private MemberAdd $addMember,
        private MemberAssignRole $assignRole
    ) {
    }

    public function handle(Group $group, array $data)
    {
        $roleIds = null;
        if (isset($data['role_ids'])) {
            $roleIds = $data['role_ids'];
            unset($data['role_ids']);
        }

        $personUuid = Uuid::uuid4();
        $person = $this->createPerson->handle(
            uuid: $personUuid,
            first_name: $data['first_name'],
            last_name: $data['last_name'],
            email: $data['email'],
            phone: isset($data['phone']) ? $data['phone'] : null,
        );

        $this->invitePerson->handle(person: $person, inviter: $group);
        
        $isContact = isset($data['is_contact']) ? $data['is_contact'] : false;
        $newMember = $this->addMember
                        ->cancelNotification()
                        ->handle($group, $person, [
                            'is_contact' => $isContact,
                            'expertise' => isset($data['expertise']) ? $data['expertise'] : null,
                            'notes' => isset($data['notes']) ? $data['notes'] : null,
                            'training_level_1' => isset($data['training_level_1']) ? $data['training_level_1'] : null,
                            'training_level_2' => isset($data['training_level_2']) ? $data['training_level_2'] : null,
                        ]);

        if ($roleIds) {
            $newMember = $this->assignRole->handle($newMember, $roleIds);
        }

        return $newMember;
    }

    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        return new MemberResource($this->handle($group, $request->all()));
    }

    public function authorize(ActionRequest $request): Response
    {
        $group = Group::findByUuidOrFail($request->uuid);
        if ($request->user()->cannot('inviteMembers', $group)) {
            return Response::deny('You do not have permission to invite members to this group.');
        }

        return Response::allow();
    }
    

    public function rules(): array
    {
        return [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:people,email',
        ];
    }
    

    public function getValidationMessages(): array
    {
        return [
            'first_name.required' => 'A first name is required.',
            'last_name.required' => 'A last name is required.',
            'email.required' => 'An email is required.',
            'email.unique' => 'A person with this email address is already in the GPM.  Please click \'Add as member\' next the the person\'s name to the right.'
        ];
    }
}
