<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Person;
use App\Services\Clerk\ClerkUserLinkService;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class MemberCandidatesList
{
    use AsController;

    public function __construct(
        private ClerkUserLinkService $clerkUserLinkService
    ) {
    }

    public function asController(Request $request, string $group)
    {
        $group = Group::findByUuidOrFail($group);

        $email = $this->normalizeEmail($request->get('email'));
        $firstName = trim((string) $request->get('first_name'));
        $lastName = trim((string) $request->get('last_name'));

        $people = collect();

        if ($email) {
            $people = Person::query()
                ->with('institution')
                ->whereRaw('lower(email) = ?', [$email])
                ->limit(10)
                ->get();
        } elseif ($firstName || $lastName) {
            $people = Person::query()
                ->with('institution')
                ->when($firstName, fn ($q) => $q->where('first_name', 'like', '%'.$firstName.'%'))
                ->when($lastName, fn ($q) => $q->where('last_name', 'like', '%'.$lastName.'%'))
                ->limit(10)
                ->get();
        }

        $candidates = $people->map(fn (Person $person) => $this->gpmCandidate($group, $person))->values();

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $clerkUser = $this->clerkUserLinkService->findByEmail($email);

            if ($clerkUser && !$this->clerkUserAlreadyExistsInGpm($clerkUser)) {
                $primaryEmail = $this->primaryEmailFromClerkUser($clerkUser);

                if ($primaryEmail) {
                    $candidates->push($this->clerkCandidate($clerkUser, $primaryEmail));
                }
            }
        }

        if (!$email && $this->canSearchClerkByName($firstName, $lastName)) {
            $clerkUsers = $this->clerkUserLinkService->searchByQuery(
                trim($firstName.' '.$lastName),
                5
            );

            foreach ($clerkUsers as $clerkUser) {
                if ($this->clerkUserAlreadyExistsInGpm($clerkUser)) {
                    continue;
                }

                $primaryEmail = $this->primaryEmailFromClerkUser($clerkUser);

                if (!$primaryEmail) {
                    continue;
                }

                $candidates->push($this->clerkCandidate($clerkUser, $primaryEmail));
            }
        }

        return response()->json([
            'data' => $candidates->values(),
        ]);
    }

    public function authorize(Request $request): Response
    {
        $groupUuid = $request->route('group');
        $group = Group::findByUuidOrFail($groupUuid);

        if ($request->user()->cannot('inviteMembers', $group)) {
            return Response::deny('You do not have permission to invite members to this group.');
        }

        return Response::allow();
    }

    protected function gpmCandidate(Group $group, Person $person): array
    {
        return [
            'type' => 'gpm_person',
            'id' => $person->id,
            'uuid' => $person->uuid,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'name' => $person->name,
            'email' => $person->email,
            'institution' => $person->institution,
            'alreadyMember' => $this->alreadyMember($group, $person),
        ];
    }

    protected function clerkCandidate(array $clerkUser, string $email): array
    {
        $firstName = data_get($clerkUser, 'first_name');
        $lastName = data_get($clerkUser, 'last_name');
        $name = trim(($firstName ?: '').' '.($lastName ?: ''));

        return [
            'type' => 'clerk_user',
            'id' => null,
            'uuid' => data_get($clerkUser, 'id'),
            'clerk_user_id' => data_get($clerkUser, 'id'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $name ?: $email,
            'email' => $email,
            'institution' => null,
            'alreadyMember' => false,
        ];
    }

    protected function alreadyMember(Group $group, Person $person): bool
    {
        return GroupMember::query()
            ->where('group_id', $group->id)
            ->where('person_id', $person->id)
            ->exists();
    }

    protected function normalizeEmail(?string $email): ?string
    {
        if (!$email) { return null; }
        $email = trim(mb_strtolower($email));
        return $email === '' ? null : $email;
    }

    protected function canSearchClerkByName(?string $firstName, ?string $lastName): bool
    {
        return mb_strlen(trim((string) $firstName)) >= 3 || mb_strlen(trim((string) $lastName)) >= 3;
    }

    protected function clerkUserAlreadyExistsInGpm(array $clerkUser): bool
    {
        $clerkUserId = data_get($clerkUser, 'id');
        $externalId = data_get($clerkUser, 'external_id');
        $primaryEmail = $this->primaryEmailFromClerkUser($clerkUser);

        return Person::query()
            ->where(function ($query) use ($clerkUserId, $externalId, $primaryEmail) {
                if ($clerkUserId) {
                    $query->orWhere('clerk_user_id', $clerkUserId);
                }

                if ($externalId) {
                    $query->orWhere('uuid', $externalId);
                }

                if ($primaryEmail) {
                    $query->orWhereRaw('lower(email) = ?', [$primaryEmail]);
                }
            })
            ->exists();
    }

    protected function primaryEmailFromClerkUser(array $clerkUser): ?string
    {
        $primaryEmailId = data_get($clerkUser, 'primary_email_address_id');
        $emailRow = collect(data_get($clerkUser, 'email_addresses', []))->first(fn ($row) => data_get($row, 'id') === $primaryEmailId);
        $email = data_get($emailRow, 'email_address');
        if (!$email) {
            $email = data_get($clerkUser, 'email_addresses.0.email_address');
        }
        return $this->normalizeEmail($email);
    }
}