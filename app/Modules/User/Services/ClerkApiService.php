<?php

namespace App\Modules\User\Services;

use Clerk\Backend\ClerkBackend;
use Clerk\Backend\Models\Operations\GetUserListRequest;
use Clerk\Backend\Models\Operations\CreateUserRequestBody;

class ClerkApiService
{
    private ClerkBackend $clerk;

    public function __construct()
    {
        $this->clerk = ClerkBackend::builder()
            ->setSecurity(config('clerk.secret_key'))
            ->build();
    }

    /**
     * Return the Clerk user ID for the given email, creating the user in Clerk
     * if they do not already exist. Returns null if the Clerk secret key is not
     * configured (e.g. local dev without Clerk set up).
     */
    public function findOrCreateByEmail(string $name, string $email): ?string
    {
        if (! config('clerk.secret_key')) {
            return null;
        }

        $clerkUserId = $this->findByEmail($email);

        if ($clerkUserId) {
            return $clerkUserId;
        }

        return $this->createUser($name, $email);
    }

    private function findByEmail(string $email): ?string
    {
        $response = $this->clerk->users->list(
            new GetUserListRequest(emailAddress: [$email])
        );

        $users = $response->userList ?? [];

        return count($users) > 0 ? ($users[0]->id ?? null) : null;
    }

    private function createUser(string $name, string $email): ?string
    {
        $nameParts = explode(' ', $name, 2);

        $response = $this->clerk->users->create(new CreateUserRequestBody(
            emailAddress: [$email],
            firstName: $nameParts[0] ?? null,
            lastName: $nameParts[1] ?? null,
            skipPasswordRequirement: true,
        ));

        return $response->user?->id ?? null;
    }
}
