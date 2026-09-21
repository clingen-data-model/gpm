<?php

namespace App\Services\Idp\Clerk;

use App\Services\Idp\IdpUser;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Services\Idp\Contracts\IdpClient;
use Illuminate\Http\Client\PendingRequest;
use App\Services\Idp\Exceptions\IdpException;
use Illuminate\Http\Client\ConnectionException;

/**
 * Thin wrapper over the Clerk Backend API (server-to-server, secret key).
 * Only the endpoints GPM needs are implemented.
 */
class ClerkClient implements IdpClient
{
    public function __construct(
        private readonly string $secret,
        private readonly string $baseUrl,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            secret: (string) config('idp.clerk.secret_key'),
            baseUrl: rtrim((string) config('idp.clerk.api_url'), '/'),
        );
    }

    public function getUser(string $id): ?IdpUser
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/users/'.rawurlencode($id)));

        if ($response->status() === 404) {
            return null;
        }
        $this->guard($response);

        return IdpUser::fromClerk($response->json());
    }

    public function findUserByEmail(string $email): ?IdpUser
    {
        return $this->first(['email_address' => [$email]]);
    }

    public function findUserByExternalId(string $externalId): ?IdpUser
    {
        return $this->first(['external_id' => [$externalId]]);
    }

    public function listUsers(int $limit, int $offset): array
    {
        $url = '/users?'.$this->queryString(['limit' => $limit, 'offset' => $offset]);
        $response = $this->send(fn (PendingRequest $http) => $http->get($url));
        $this->guard($response);

        return array_map(IdpUser::fromClerk(...), (array) $response->json());
    }

    public function createUser(array $attributes): IdpUser
    {
        $response = $this->send(fn (PendingRequest $http) => $http->post('/users', $this->toClerkPayload($attributes)));
        $this->guard($response);

        return IdpUser::fromClerk($response->json());
    }

    public function updateUser(string $id, array $attributes): IdpUser
    {
        $response = $this->send(fn (PendingRequest $http) => $http->patch('/users/'.rawurlencode($id), $this->toClerkPayload($attributes)));
        $this->guard($response);

        return IdpUser::fromClerk($response->json());
    }

    public function updatePassword(string $id, string $password): void
    {
        $response = $this->send(fn (PendingRequest $http) => $http->patch('/users/'.rawurlencode($id), [
            'password' => $password,
            'skip_password_checks' => true,
            'sign_out_of_other_sessions' => false,
        ]));
        $this->guard($response);
    }

    /**
     * Map provider-neutral attributes to Clerk's create/update user body.
     */
    private function toClerkPayload(array $attributes): array
    {
        $payload = [];

        if (array_key_exists('email', $attributes)) {
            $payload['email_address'] = [$attributes['email']];
        }
        foreach (['first_name', 'last_name', 'external_id', 'password', 'password_digest', 'password_hasher', 'created_at', 'public_metadata', 'private_metadata'] as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== null) {
                $payload[$key] = $attributes[$key];
            }
        }
        if (isset($payload['password']) || isset($payload['password_digest'])) {
            // Imported credentials are trusted as-is; Clerk's strength checks
            // and breach lookups are for passwords chosen inside Clerk.
            $payload['skip_password_checks'] = true;
        }
        if (! isset($payload['password']) && ! isset($payload['password_digest']) && array_key_exists('email', $attributes)) {
            $payload['skip_password_requirement'] = true;
        }

        return $payload;
    }

    private function first(array $query): ?IdpUser
    {
        $url = '/users?'.$this->queryString($query + ['limit' => 1]);
        $response = $this->send(fn (PendingRequest $http) => $http->get($url));
        $this->guard($response);

        $users = $response->json();

        return isset($users[0]) ? IdpUser::fromClerk($users[0]) : null;
    }

    /**
     * Clerk's list filters take a repeated key (email_address=a&email_address=b);
     * the bracketed arrays http_build_query() produces are ignored, which makes
     * the filter silently match every user.
     */
    private function queryString(array $query): string
    {
        $parts = [];

        foreach ($query as $key => $value) {
            foreach ((array) $value as $item) {
                $parts[] = rawurlencode($key).'='.rawurlencode((string) $item);
            }
        }

        return implode('&', $parts);
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->secret)->acceptJson()->baseUrl($this->baseUrl)->timeout(15);
    }

    private function send(callable $request): Response
    {
        try {
            return $request($this->http());
        } catch (ConnectionException $e) {
            throw new IdpException('Could not reach Clerk: '.$e->getMessage(), null, [], $e);
        }
    }

    private function guard(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $errors = [];
        foreach ((array) $response->json('errors', []) as $error) {
            $errors[$error['meta']['param_name'] ?? $error['code'] ?? count($errors)] = $error['code'] ?? $error['message'] ?? 'error';
        }
        $message = $response->json('errors.0.long_message')
            ?? $response->json('errors.0.message')
            ?? 'Clerk request failed with HTTP '.$response->status();

        $retryAfter = $response->header('Retry-After');

        throw new IdpException(
            $message,
            $response->status(),
            $errors,
            retryAfter: is_numeric($retryAfter) ? (int) $retryAfter : null,
        );
    }
}
