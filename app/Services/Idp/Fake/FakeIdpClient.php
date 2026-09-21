<?php

namespace App\Services\Idp\Fake;

use Throwable;
use Illuminate\Support\Str;
use App\Services\Idp\IdpUser;
use Illuminate\Support\Carbon;
use App\Services\Idp\Contracts\IdpClient;
use App\Services\Idp\Exceptions\IdpException;

/**
 * IdpClient backed by the fake store. Enforces the same uniqueness rules as
 * Clerk (email and external_id must be unique) so tests exercise real
 * failure paths, and can be told to fail the next call.
 */
class FakeIdpClient implements IdpClient
{
    private ?Throwable $failNext = null;

    /** @var array<int, array{method: string, args: array}> */
    private array $calls = [];

    public function __construct(private readonly FakeIdpStore $store)
    {
    }

    public function store(): FakeIdpStore
    {
        return $this->store;
    }

    /**
     * Make the next client call throw, e.g. to simulate an outage.
     */
    public function failNext(?Throwable $exception = null): void
    {
        $this->failNext = $exception ?? new IdpException('Fake IdP is unavailable.', 503);
    }

    /** @return array<int, array{method: string, args: array}> */
    public function calls(): array
    {
        return $this->calls;
    }

    public function getUser(string $id): ?IdpUser
    {
        $this->record(__FUNCTION__, func_get_args());
        $record = $this->store->find($id);

        return $record ? $this->toIdpUser($record) : null;
    }

    public function findUserByEmail(string $email): ?IdpUser
    {
        $this->record(__FUNCTION__, func_get_args());
        $record = $this->store->findByEmail($email);

        return $record ? $this->toIdpUser($record) : null;
    }

    public function findUserByExternalId(string $externalId): ?IdpUser
    {
        $this->record(__FUNCTION__, func_get_args());
        $record = $this->store->findByExternalId($externalId);

        return $record ? $this->toIdpUser($record) : null;
    }

    public function listUsers(int $limit, int $offset): array
    {
        $this->record(__FUNCTION__, func_get_args());

        $page = array_slice(array_values($this->store->all()), $offset, $limit);

        return array_map($this->toIdpUser(...), $page);
    }

    public function createUser(array $attributes): IdpUser
    {
        $this->record(__FUNCTION__, func_get_args());

        $email = $attributes['email'] ?? null;
        if (! $email) {
            throw new IdpException('email is required.', 422, ['email' => 'required']);
        }
        if ($this->store->findByEmail($email)) {
            throw new IdpException('That email address is taken. Please try another.', 422, ['email' => 'form_identifier_exists']);
        }
        $externalId = $attributes['external_id'] ?? null;
        if ($externalId && $this->store->findByExternalId($externalId)) {
            throw new IdpException('external_id already exists.', 422, ['external_id' => 'form_identifier_exists']);
        }

        $record = $this->store->put([
            'id' => $this->store->nextId(),
            'email' => $email,
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'] ?? null,
            'external_id' => $externalId,
            'password_digest' => $this->digestFrom($attributes),
            'created_at' => $attributes['created_at'] ?? Carbon::now()->toRfc3339String(),
        ]);

        return $this->toIdpUser($record);
    }

    public function updateUser(string $id, array $attributes): IdpUser
    {
        $this->record(__FUNCTION__, func_get_args());

        $record = $this->store->find($id);
        if (! $record) {
            throw new IdpException('Not found.', 404);
        }

        foreach (['email', 'first_name', 'last_name', 'external_id'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $record[$key] = $attributes[$key];
            }
        }
        if (isset($attributes['password']) || isset($attributes['password_digest'])) {
            $record['password_digest'] = $this->digestFrom($attributes);
        }

        return $this->toIdpUser($this->store->put($record));
    }

    public function updatePassword(string $id, string $password): void
    {
        $this->updateUser($id, ['password' => $password]);
    }

    /**
     * Check a plaintext password against the stored digest (tests only).
     */
    public function passwordMatches(string $id, string $password): bool
    {
        $record = $this->store->find($id);

        return $record && ! empty($record['password_digest'])
            && password_verify($password, $record['password_digest']);
    }

    private function digestFrom(array $attributes): ?string
    {
        if (! empty($attributes['password_digest'])) {
            return $attributes['password_digest'];
        }
        if (! empty($attributes['password'])) {
            return password_hash($attributes['password'], PASSWORD_BCRYPT, ['cost' => 4]);
        }

        return null;
    }

    private function toIdpUser(array $record): IdpUser
    {
        return new IdpUser(
            id: $record['id'],
            email: $record['email'] ?? null,
            firstName: $record['first_name'] ?? null,
            lastName: $record['last_name'] ?? null,
            externalId: $record['external_id'] ?? null,
            raw: $record,
        );
    }

    private function record(string $method, array $args): void
    {
        $this->calls[] = ['method' => $method, 'args' => $args];

        if ($this->failNext) {
            $exception = $this->failNext;
            $this->failNext = null;
            throw $exception;
        }
    }
}
