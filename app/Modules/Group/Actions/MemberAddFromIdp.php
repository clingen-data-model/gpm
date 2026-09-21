<?php

namespace App\Modules\Group\Actions;

use Ramsey\Uuid\Uuid;
use App\Services\Idp\IdpUser;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use App\Providers\IdpServiceProvider;
use App\Modules\Group\Models\GroupMember;
use App\Services\Idp\Contracts\IdpClient;
use App\Modules\Person\Actions\PersonCreate;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsController;
use App\Services\Idp\Exceptions\IdpException;
use Illuminate\Validation\ValidationException;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\User\Actions\UserCreateFromIdpIdentity;
use App\Modules\Group\Notifications\AddedToGroupNotification;

/**
 * Add a group member from an identity at the external identity provider:
 * a ClinGen account that has no GPM login yet. Finds or creates the Person,
 * links a User to the identity (redeeming any pending invite), adds the
 * membership and tells the person their existing account signs them in.
 *
 * The identity is re-read from the provider so a stale suggestion can never
 * bind an address the account does not hold.
 */
class MemberAddFromIdp
{
    use AsController;

    public function __construct(
        private IdpClient $client,
        private PersonCreate $createPerson,
        private UserCreateFromIdpIdentity $createUser,
        private MemberAdd $addMember,
        private MemberAssignRole $assignRole,
    ) {
    }

    /**
     * @param  array{first_name?: ?string, last_name?: ?string}  $names  Coordinator-typed names for a new person.
     */
    public function handle(Group $group, IdpUser $idpUser, string $email, ?Person $person, array $names, array $data, ?array $roleIds = null): GroupMember
    {
        return DB::transaction(function () use ($group, $idpUser, $email, $person, $names, $data, $roleIds) {
            $person = $this->resolvePerson($person, $idpUser, $email, $names);
            $this->guardNotAlreadyMember($group, $person);

            $this->createUser->handle($person, $idpUser, $email);

            $member = $this->addMember->cancelNotification()->handle($group, $person, $data);
            if ($roleIds) {
                $member = $this->assignRole->handle($member, $roleIds);
            }

            Notification::send($person, new AddedToGroupNotification($group, idpAccountLinked: true));

            return $member;
        });
    }

    public function asController(ActionRequest $request, Group $group)
    {
        abort_unless(IdpServiceProvider::enabled(), 404);

        try {
            $idpUser = $this->client->getUser($request->idp_id);
        } catch (IdpException $e) {
            return response()->json(['message' => 'The identity provider could not be reached. Nothing was changed; please try again.'], 503);
        }
        if (! $idpUser) {
            throw ValidationException::withMessages(['idp_id' => ['That ClinGen account could not be found.']]);
        }
        if (! $idpUser->hasEmail($request->email)) {
            throw ValidationException::withMessages(['email' => ['That address is not a verified address on this ClinGen account.']]);
        }

        $data = $request->only(['is_contact', 'notes']);
        if ($group->is_vcep_or_scvcep) {
            $data = array_merge($data, $request->only(['training_level_1', 'training_level_2']));
        }

        $member = $this->handle(
            group: $group,
            idpUser: $idpUser,
            email: $request->email,
            person: $request->person_id ? Person::findOrFail($request->person_id) : null,
            names: $request->only(['first_name', 'last_name']),
            data: $data,
            roleIds: $request->role_ids ?: null,
        );

        $member->load('cois', 'person', 'group');

        return new MemberResource($member);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('inviteMembers', $request->group);
    }

    public function rules(): array
    {
        return [
            'idp_id' => 'required|string|max:255',
            'email' => 'required|email',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'person_id' => 'nullable|integer|exists:people,id',
            'role_ids' => 'nullable|array',
            'is_contact' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'training_level_1' => 'nullable|boolean',
            'training_level_2' => 'nullable|boolean',
        ];
    }

    private function resolvePerson(?Person $person, IdpUser $idpUser, string $email, array $names): Person
    {
        if ($person) {
            if ($person->user_id) {
                throw ValidationException::withMessages(['person_id' => ['This person already has a GPM account; add them as a member directly.']]);
            }

            return $person;
        }

        $existing = Person::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->orderBy('id')->first();
        if ($existing) {
            if ($existing->user_id) {
                throw ValidationException::withMessages(['email' => ['A person with this address already has a GPM account; add them as a member directly.']]);
            }

            return $existing;
        }

        [$firstName, $lastName] = $this->namesFor($idpUser, $email, $names);

        return $this->createPerson->handle(
            uuid: Uuid::uuid4()->toString(),
            first_name: $firstName,
            last_name: $lastName,
            email: $email,
        );
    }

    /**
     * Coordinator-typed names win, then the identity's, then the address's
     * local part so PersonCreate always receives strings.
     *
     * @return array{0: string, 1: string}
     */
    private function namesFor(IdpUser $idpUser, string $email, array $names): array
    {
        $first = trim((string) ($names['first_name'] ?? '')) ?: trim((string) $idpUser->firstName);
        $last = trim((string) ($names['last_name'] ?? '')) ?: trim((string) $idpUser->lastName);

        if ($first === '' && $last === '') {
            $local = explode('@', $email)[0];
            $parts = preg_split('/[._-]+/', $local, 2) ?: [$local];
            $first = ucfirst($parts[0]);
            $last = isset($parts[1]) ? ucfirst($parts[1]) : '';
        }

        return [$first, $last];
    }

    private function guardNotAlreadyMember(Group $group, Person $person): void
    {
        if (! $person->exists) {
            return;
        }
        if (GroupMember::query()->where('group_id', $group->id)->where('person_id', $person->id)->exists()) {
            throw ValidationException::withMessages(['person_id' => ['This person is already a member of this group.']]);
        }
    }
}
