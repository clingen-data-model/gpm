<?php

namespace App\Services\Clerk;

use App\Modules\User\Models\User; // adjust if your User model namespace is different
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

    protected function setExternalId(string $clerkUserId, string $personUuid): void
    {
        $response = $this->clientFactory->make()->patch("/users/{$clerkUserId}", [
            'external_id' => $personUuid,
        ]);

        $response->throw();
    }
}