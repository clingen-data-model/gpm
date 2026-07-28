<?php

namespace App\Services\Clerk;

use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $email = $this->normalizeEmail($email);

        if (!$email) {
            return null;
        }

        $response = $this->clientFactory->make()->get('/users', [
            'query' => $email,
            'limit' => 10,
        ]);

        $response->throw();

        $matches = collect($this->usersFromClerkListResponse($response->json()));

        logger()->info('Clerk findByEmail response', [
            'email' => $email,
            'count' => $matches->count(),
            'ids' => $matches->pluck('id')->values()->all(),
        ]);

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
            ->map(fn ($row) => $this->normalizeEmail(data_get($row, 'email_address')))
            ->contains($email);
    }

    protected function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = trim(mb_strtolower($email));

        return $email === '' ? null : $email;
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
}