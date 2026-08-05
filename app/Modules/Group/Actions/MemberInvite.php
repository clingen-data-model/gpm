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

use App\Modules\Person\Models\Person;
use App\Modules\User\Models\User;
use App\Services\Clerk\ClerkUserLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Modules\Person\Events\PersonInvited;
use App\Services\UserIdentityNormalizer;

class MemberInvite
{
    use AsController;
    use AsObject;

    public function __construct(
        private PersonCreate $createPerson,
        private PersonInvite $invitePerson,
        private MemberAdd $addMember,
        private MemberAssignRole $assignRole,
        private ClerkInvitationService $clerkInvitationService,
        private ClerkUserLinkService $clerkUserLinkService
    ) {
    }

    public function handle(Group $group, array $data): GroupMember
    {
        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        $email = UserIdentityNormalizer::normalizeEmail($data['email']);

        // A. Existing GPM person
        $person = Person::query()->whereRaw('lower(email) = ?', [$email])->first();
        if ($person) {
            return $this->addPersonToGroup($group, $person, $data, $roleIds, $sendAddedToGroupNotification = true);
        }

        // B. Existing Clerk user, not yet in GPM
        $clerkUser = $this->clerkUserLinkService->findByEmail($email);
        if ($clerkUser) {
            $person = $this->createGpmPersonFromClerkUser($clerkUser, $data, $email);
            return $this->addPersonToGroup($group, $person, $data, $roleIds, $sendAddedToGroupNotification = true);
        }

        // C. Brand-new person
        return $this->inviteBrandNewPerson($group, $data, $roleIds, $email);
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
            'email' => 'required|email',
        ];
    }


    public function getValidationMessages(): array
    {
        return [
            'first_name.required' => 'A first name is required.',
            'last_name.required' => 'A last name is required.',
            'email.required' => 'An email is required.',
        ];
    }

    protected function createGpmPersonFromClerkUser(array $clerkUser, array $data, string $email): Person
    {
        $clerkUserId = data_get($clerkUser, 'id');

        if (!$clerkUserId) {
            throw new \RuntimeException('Clerk user did not include an id.');
        }

        $externalId = data_get($clerkUser, 'external_id');

        if ($externalId) {
            $existingPersonForUuid = Person::where('uuid', $externalId)->first();
            if ($existingPersonForUuid) {
                throw ValidationException::withMessages(['email' => ["This Clerk user is already linked to {$existingPersonForUuid->email} in GPM."]]);
            }
            $personUuid = $externalId;
        } else {
            $personUuid = Uuid::uuid4()->toString();
            $this->clerkUserLinkService->setExternalId($clerkUserId, $personUuid);
        }

        return DB::transaction(function () use ($data, $email, $personUuid, $clerkUserId) {
            $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
            $user = User::firstOrCreate(['email' => $email], [
                    'name' => $name,
                    'password' => Hash::make(Str::random(40)),
                ]
            );

            $person = $this->createPerson->handle(
                uuid: $personUuid,
                first_name: $data['first_name'],
                last_name: $data['last_name'],
                email: $email,
                phone: valueAtIndex($data, 'phone'),
            );

            $person->forceFill(['user_id' => $user->id, 'clerk_user_id' => $clerkUserId])->save();
            $this->clerkUserLinkService->addApplication($clerkUserId, 'GPM');

            return $person;
        });
    }

    protected function inviteBrandNewPerson(Group $group, array $data, ?array $roleIds, string $email): GroupMember
    {
        $person = $this->createPerson->handle(
            uuid: Uuid::uuid4(),
            first_name: $data['first_name'],
            last_name: $data['last_name'],
            email: $email,
            phone: valueAtIndex($data, 'phone'),
        );

        $invite = $this->invitePerson->handle(person: $person, inviter: $group, dispatchEvent: false);
        $clerkInvitation = $this->clerkInvitationService->createForInvite($invite, $group);
        $clerkExpiresAt = data_get($clerkInvitation, 'expires_at');
        $invite->update([
            'clerk_invitation_id' => data_get($clerkInvitation, 'id'),
            'clerk_invitation_url' => data_get($clerkInvitation, 'url'),
            'expires_at' => $clerkExpiresAt ? Carbon::createFromTimestampMs($clerkExpiresAt) : now()->addDays(30),
        ]);
        Event::dispatch(new PersonInvited($invite->fresh()));
        return $this->addPersonToGroup($group, $person, $data, $roleIds, $sendAddedToGroupNotification = false);
    }

    protected function addPersonToGroup(Group $group, Person $person, array $data, ?array $roleIds, bool $sendAddedToGroupNotification = true): GroupMember 
    {
        $memberAdd = $sendAddedToGroupNotification ? $this->addMember->sendNotification() : $this->addMember->cancelNotification();
        $newMember = $memberAdd->handle($group, $person, [
                        'is_contact' => valueAtIndex($data, 'is_contact', false),
                        'notes' => valueAtIndex($data, 'notes'),
                        'training_level_1' => valueAtIndex($data, 'training_level_1'),
                        'training_level_2' => valueAtIndex($data, 'training_level_2'),
                    ]);
        if ($roleIds) {
            $newMember = $this->assignRole->handle($newMember, $roleIds);
        }
        return $newMember;
    }
}
