<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use App\Services\Clerk\ClerkUserLinkService;
use App\Services\UserIdentityNormalizer;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class PersonFindClerkAccount
{
    use AsController;

    public function __construct(
        private ClerkUserLinkService $clerkUserLinkService
    ) {
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $email = UserIdentityNormalizer::normalizeEmail($request->get('email'));

        if (!$email) {
            return response()->json(['data' => null]);
        }

        $clerkUser = $this->clerkUserLinkService->findByEmail($email);

        return response()->json([
            'data' => $clerkUser ? $this->candidate($clerkUser, $email, $person) : null,
        ]);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasAnyRole(['super-admin', 'super-user']);
    }

    protected function candidate(array $clerkUser, string $email, Person $person): array
    {
        $clerkUserId = data_get($clerkUser, 'id');
        $externalId = data_get($clerkUser, 'external_id');

        $linkedPerson = $clerkUserId
            ? Person::query()->where('clerk_user_id', $clerkUserId)->first()
            : null;

        $externalIdPerson = $externalId
            ? Person::query()->where('uuid', $externalId)->first()
            : null;

        $firstName = data_get($clerkUser, 'first_name');
        $lastName = data_get($clerkUser, 'last_name');
        $name = trim(($firstName ?: '').' '.($lastName ?: ''));

        return [
            'clerk_user_id' => $clerkUserId,
            'external_id' => $externalId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $name ?: $email,
            'email' => $email,
            'applications' => data_get($clerkUser, 'private_metadata.applications', []),

            'linked_person_uuid' => $linkedPerson?->uuid,
            'linked_person_name' => $linkedPerson?->name,
            'external_id_person_uuid' => $externalIdPerson?->uuid,
            'external_id_person_name' => $externalIdPerson?->name,

            'will_update_person_uuid' => $externalId && $externalId !== $person->uuid,
        ];
    }
}