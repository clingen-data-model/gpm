<?php

namespace App\Modules\Group\Actions;

use Throwable;
use Ramsey\Uuid\Uuid;
use App\Providers\IdpServiceProvider;
use App\Services\Idp\Contracts\IdpClient;
use Illuminate\Validation\ValidationException;
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
use App\Modules\Group\Models\GroupMember;

class MemberInvite
{
    use AsController;
    use AsObject;

    public const EXISTING_IDP_ACCOUNT_MESSAGE = 'This address belongs to an existing ClinGen account. Click \'Add using ClinGen account\' next to it on the right instead of inviting.';

    public function __construct(
        private PersonCreate $createPerson,
        private PersonInvite $invitePerson,
        private MemberAdd $addMember,
        private MemberAssignRole $assignRole,
        private IdpClient $idpClient,
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

        $this->invitePerson->handle(person: $person, inviter: $group);

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
        $this->guardAgainstExistingIdpAccount($request->email);

        return new MemberResource($this->handle($group, $request->all()));
    }

    /**
     * An invitee with a ClinGen account must be added through that account
     * (MemberAddFromIdp) rather than pick a second, unrelated password. An
     * unreachable IdP does not block the invitation.
     */
    private function guardAgainstExistingIdpAccount(?string $email): void
    {
        if (! $email || ! IdpServiceProvider::enabled()) {
            return;
        }

        try {
            $identity = $this->idpClient->findUserByEmail($email);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        if ($identity) {
            throw ValidationException::withMessages(['email' => [self::EXISTING_IDP_ACCOUNT_MESSAGE]]);
        }
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
