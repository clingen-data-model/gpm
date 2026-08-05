<?php

namespace App\Services\Clerk;

use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\UserIdentityNormalizer;

class ClerkUserLinkService
{
    public function __construct(
        private ClerkClientFactory $clientFactory
    ) {
    }

    public function getUser(string $clerkUserId): array
    {
        $response = $this->clientFactory->make()->get("/users/{$clerkUserId}");
        $response->throw();

        return $response->json();
    }

    public function linkInvite(Invite $invite, string $clerkUserId): Person
    {
        $person = $invite->person;

        if (!$person) {
            throw ValidationException::withMessages([
                'invite' => 'Invite is missing an associated person.',
            ]);
        }

        $clerkUser = $this->getUser($clerkUserId);

        $emails = collect(data_get($clerkUser, 'email_addresses', []))
            ->map(fn ($row) => strtolower((string) data_get($row, 'email_address')))
            ->filter()
            ->values();

        if (!$emails->contains(strtolower($invite->email))) {
            throw ValidationException::withMessages([
                'email' => 'The invited email address is not present on the Clerk account. Please resolve that in Clerk first.',
            ]);
        }

        $alreadyLinkedElsewhere = Person::query()
            ->where('clerk_user_id', $clerkUserId)
            ->where('id', '!=', $person->id)
            ->exists();

        if ($alreadyLinkedElsewhere) {
            throw ValidationException::withMessages([
                'clerk_user_id' => 'This Clerk account is already linked to another person in GPM.',
            ]);
        }

        $legacyUser = $this->ensureLegacyUser($person);

        $person->forceFill([
            'clerk_user_id' => $clerkUserId,
            'user_id' => $person->user_id ?: $legacyUser->id,
        ])->save();

        $invite->forceFill([
            'redeemed_at' => now(),
        ])->save();

        $this->setExternalId($clerkUserId, $person->uuid);
        $this->addApplication($clerkUserId, 'GPM');

        return $person->fresh();
    }

    protected function ensureLegacyUser(Person $person): User
    {
        if ($person->user_id) {
            return User::findOrFail($person->user_id);
        }

        $user = User::firstOrCreate(
            ['email' => $person->email],
            [
                'name' => trim($person->first_name . ' ' . $person->last_name),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
            ]
        );

        return $user;
    }

    public function setExternalId(string $clerkUserId, string $personUuid): void
    {
        $response = $this->clientFactory->make()->patch("/users/{$clerkUserId}", [
            'external_id' => $personUuid,
        ]);

        $response->throw();
    }

    public function findByEmail(?string $email): ?array
    {
        $email = UserIdentityNormalizer::normalizeEmail($email);
        if (!$email) { return null; }
        $response = $this->clientFactory->make()->get('/users', ['query' => $email, 'limit' => 10]);
        $response->throw();
        $matches = collect($this->usersFromClerkListResponse($response->json()));
        return $matches->first(fn ($user) => $this->clerkUserHasEmail($user, $email));
    }

    protected function usersFromClerkListResponse(?array $body): array
    {
        if (!$body) {
            return [];
        }

        // Clerk direct API response from /users is a plain list: [ [user1], [user2], ... ]
        if (array_keys($body) === range(0, count($body) - 1)) {
            return $body;
        }

        // Keep this fallback in case another SDK/client returns: [ 'data' => [ [user1], [user2] ] ]
        if (isset($body['data']) && is_array($body['data'])) {
            return $body['data'];
        }

        return [];
    }

    protected function clerkUserHasEmail(array $user, string $email): bool
    {
        return collect(data_get($user, 'email_addresses', []))
            ->map(fn ($row) => UserIdentityNormalizer::normalizeEmail(data_get($row, 'email_address')))
            ->contains($email);
    }

    public function addApplication(string $clerkUserId, string $application): void
    {
        $clerkUser = $this->getUser($clerkUserId);

        $currentApplications = data_get($clerkUser, 'private_metadata.applications', []);

        if (is_string($currentApplications)) {
            $currentApplications = [$currentApplications];
        }

        if (!is_array($currentApplications)) {
            $currentApplications = [];
        }

        $applications = collect($currentApplications)->push($application)->filter()->unique()->values()->all();
        $response = $this->clientFactory->make()->patch("/users/{$clerkUserId}/metadata", [
            'private_metadata' => [
                'applications' => $applications,
            ],
        ]);

        $response->throw();
    }

    public function searchByQuery(?string $query, int $limit = 10): array
    {
        $query = trim((string) $query);
        if (mb_strlen($query) < 3) { return []; }
        $response = $this->clientFactory->make()->get('/users', ['query' => $query, 'limit' => $limit]);
        $response->throw();
        return $this->usersFromClerkListResponse($response->json());
    }

    public function unlinkGpmApplicationFromPerson(Person $person): void
    {
        $clerkUserId = $person->clerk_user_id;
        if (!$clerkUserId) { return; }
        $this->removeApplication($clerkUserId, 'GPM');
        $person->forceFill(['clerk_user_id' => null])->save();
    }

    public function removeApplication(string $clerkUserId, string $application): void
    {
        // Clear 'GPM' from the Clerk user's private_metadata.applications array, if present
        $clerkUser = $this->getUser($clerkUserId);
        $currentApplications = data_get($clerkUser, 'private_metadata.applications', []);
        if (is_string($currentApplications)) {
            $currentApplications = [$currentApplications];
        }

        if (!is_array($currentApplications)) {
            $currentApplications = [];
        }

        $applications = collect($currentApplications)->reject(fn ($app) => $app === $application)->values()->all();
        $response = $this->clientFactory->make()->patch("/users/{$clerkUserId}/metadata", [
            'private_metadata' => ['applications' => $applications],
        ]);
        $response->throw();
    }

    public function relinkPersonToClerkAccount(Person $person, string $clerkUserId): Person
    {
        $clerkUser = $this->getUser($clerkUserId);
        $alreadyLinkedElsewhere = Person::query()->where('clerk_user_id', $clerkUserId)->where('id', '!=', $person->id)->exists();
        if ($alreadyLinkedElsewhere) {
            throw ValidationException::withMessages(['clerk_user_id' => 'This Clerk account is already linked to another person in GPM.']);
        }

        $externalId = trim((string) data_get($clerkUser, 'external_id'));
        $resolvedPersonUuid = $person->uuid;
        if ($externalId) {
            if (!\Illuminate\Support\Str::isUuid($externalId)) {
                throw ValidationException::withMessages([
                    'external_id' => 'This Clerk account has an external ID, but it is not a valid UUID. Please review this manually.',
                ]);
            }

            $uuidUsedByAnotherPerson = Person::query()->where('uuid', $externalId)->where('id', '!=', $person->id)->exists();
            if ($uuidUsedByAnotherPerson) {
                throw ValidationException::withMessages([
                    'external_id' => 'This Clerk external ID already belongs to another person in GPM.',
                ]);
            }

            // Important: Clerk already has a ClinGen identity UUID, so GPM adopts it.
            $resolvedPersonUuid = $externalId;
        } else {
            // Clerk has no external identity yet, so use the current GPM person UUID.
            $this->setExternalId($clerkUserId, $person->uuid);
        }

        $oldClerkUserId = $person->clerk_user_id;
        $this->addApplication($clerkUserId, 'GPM');

        DB::transaction(function () use ($person, $clerkUserId, $resolvedPersonUuid) {
            $person->forceFill([
                'uuid' => $resolvedPersonUuid,
                'clerk_user_id' => $clerkUserId,
            ])->save();
        });

        if ($oldClerkUserId && $oldClerkUserId !== $clerkUserId) {
            $this->removeApplication($oldClerkUserId, 'GPM');
        }

        return $this->syncPersonAndUserFromClerk($person->fresh(), $clerkUser);
    }

    public function syncPersonAndUserFromClerk(Person $person, ?array $clerkUser = null): Person
    {
        if (!$clerkUser) {
            if (!$person->clerk_user_id) {
                throw ValidationException::withMessages([
                    'clerk_user_id' => 'This person is not linked to a Clerk account.',
                ]);
            }
            $clerkUser = $this->getUser($person->clerk_user_id);
        }

        $attributes = $this->profileAttributesFromClerkUser($clerkUser);
        if (!$attributes['email']) {
            throw ValidationException::withMessages([
                'email' => 'The linked Clerk account does not have a primary email address.',
            ]);
        }

        $this->assertPersonEmailAvailable($person, $attributes['email']);

        return DB::transaction(function () use ($person, $attributes) {
            $user = $this->syncLegacyUserForPerson($person, $attributes['email'], $attributes['name']);
            $person->forceFill([
                'first_name' => $attributes['first_name'] ?: $person->first_name,
                'last_name' => $attributes['last_name'] ?: $person->last_name,
                'email' => $attributes['email'],
                'user_id' => $person->user_id ?: $user->id,
            ])->save();

            return $person->fresh();
        });
    }

    protected function profileAttributesFromClerkUser(array $clerkUser): array
    {
        $firstName = UserIdentityNormalizer::normalizeNamePart(data_get($clerkUser, 'first_name'));
        $lastName = UserIdentityNormalizer::normalizeNamePart(data_get($clerkUser, 'last_name'));
        $email = UserIdentityNormalizer::normalizeEmail($this->primaryEmailFromClerkUser($clerkUser));
        $name = trim(($firstName ?: '').' '.($lastName ?: ''));

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'name' => $name ?: $email,
        ];
    }

    protected function primaryEmailFromClerkUser(array $clerkUser): ?string
    {
        $primaryEmailId = data_get($clerkUser, 'primary_email_address_id');
        $emailRow = collect(data_get($clerkUser, 'email_addresses', []))->first(fn ($row) => data_get($row, 'id') === $primaryEmailId);
        return data_get($emailRow, 'email_address') ?: data_get($clerkUser, 'email_addresses.0.email_address');
    }

    protected function assertPersonEmailAvailable(Person $person, string $email): void
    {
        $emailUsedByAnotherPerson = Person::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->where('id', '!=', $person->id)
            ->exists();

        if ($emailUsedByAnotherPerson) {
            throw ValidationException::withMessages([
                'email' => 'The primary email from Clerk is already used by another person in GPM.',
            ]);
        }
    }

    protected function syncLegacyUserForPerson(Person $person, string $email, ?string $name): User
    {
        $name = $name ?: $email;
        $existingUser = $person->user()->first();

        if ($existingUser) {
            $emailUsedByAnotherUser = User::query()->whereRaw('lower(email) = ?', [$email])->where('id', '!=', $existingUser->id)->exists();
            if ($emailUsedByAnotherUser) {
                throw ValidationException::withMessages([
                    'email' => 'The primary email from Clerk is already used by another GPM user account.',
                ]);
            }

            $existingUser->forceFill([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $existingUser->email_verified_at ?: now(),
            ])->save();
            return $existingUser;
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();
        if ($user) {
            $userUsedByAnotherPerson = Person::query()->where('user_id', $user->id)->where('id', '!=', $person->id)->exists();
            if ($userUsedByAnotherPerson) {
                throw ValidationException::withMessages([
                    'email' => 'The primary email from Clerk is already linked to another person in GPM.',
                ]);
            }

            $user->forceFill([
                'name' => $name,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();

            return $user;
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(40)),
        ]);
    }
}