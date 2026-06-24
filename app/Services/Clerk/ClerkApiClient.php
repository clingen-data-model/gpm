<?php

namespace App\Services\Clerk;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

/**
 * Thin wrapper over the Clerk Backend API (server-to-server, secret key).
 *
 * Used for migrating existing users into Clerk. Only the few endpoints needed
 * for import are implemented.
 */
class ClerkApiClient
{
    public function __construct(
        private readonly string $secret,
        private readonly string $baseUrl,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            secret: (string) config('clerk.secret_key'),
            baseUrl: rtrim((string) config('clerk.api_url'), '/'),
        );
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->secret)->acceptJson()->baseUrl($this->baseUrl);
    }

    /**
     * Return the Clerk user id that owns $email, or null if none exists.
     */
    public function findUserIdByEmail(string $email): ?string
    {
        $response = $this->http()
            ->get('/users', ['email_address' => [$email]])
            ->throw();

        $users = $response->json();

        // The list endpoint returns a bare array of user objects.
        return $users[0]['id'] ?? null;
    }

    /**
     * Create a Clerk user. Returns the raw Clerk user object (caller reads `id`).
     */
    public function createUser(array $payload): Response
    {
        return $this->http()->post('/users', $payload);
    }
}
