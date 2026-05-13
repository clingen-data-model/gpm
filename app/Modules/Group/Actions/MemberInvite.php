<?php

namespace App\Modules\Group\Actions;

use Ramsey\Uuid\Uuid;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
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
use App\Modules\Group\Models\GroupMember;
use App\Services\Clerk\ClerkInvitationService;
use Carbon\Carbon;

class MemberInvite
{
    use AsController;
    use AsObject;

    public function __construct(
        private PersonCreate $createPerson,
        private PersonInvite $invitePerson,
        private MemberAdd $addMember,
        private MemberAssignRole $assignRole,
        private ClerkInvitationService $clerkInvitationService
    ) {
    }

    public function handle(Group $group, array $data): GroupMember
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
            phone: valueAtIndex($data, 'phone'),
        );        

        /* beginning of the clerk */
        $invite = $this->invitePerson->handle(person: $person, inviter: $group);
        $clerkInvitation = $this->clerkInvitationService->createForInvite($invite, $group);
        $clerkExpiresAt = data_get($clerkInvitation, 'expires_at');

        logger()->info('Clerk invitation response', $clerkInvitation);

        $invite->update([
            'clerk_invitation_id' => data_get($clerkInvitation, 'id'),
            'expires_at' => $clerkExpiresAt ? Carbon::createFromTimestampMs($clerkExpiresAt) : now()->addDays(30),
        ]);
        logger()->info('Local invite updated', [
            'invite_id' => $invite->id,
            'clerk_invitation_id' => $invite->fresh()->clerk_invitation_id,
            'expires_at' => optional($invite->fresh()->expires_at)?->toDateTimeString(),
        ]);
        /* end of the clerk */

        $isContact = valueAtIndex($data, 'is_contact', false);
        $newMember = $this->addMember
                        ->cancelNotification()
                        ->handle($group, $person, [
                            'is_contact' => $isContact,
                            'notes' => valueAtIndex($data, 'notes'),
                            'training_level_1' => valueAtIndex($data, 'training_level_1'),
                            'training_level_2' => valueAtIndex($data, 'training_level_2'),
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
            'email.unique' => 'A person with this email address is already in the GPM.  Please click \'Add as member\' next the person\'s name to the right.'
        ];
    }
}
