<?php

namespace App\Services\Idp\Contracts;

use App\Services\Idp\IdpUser;
use App\Services\Idp\Exceptions\IdpException;

/**
 * Server-to-server access to the identity provider's user directory.
 *
 * User attributes are provider-neutral: email, first_name, last_name,
 * external_id, password (plaintext), password_digest + password_hasher,
 * created_at (RFC3339). Implementations map them to the provider's API.
 */
interface IdpClient
{
    public function getUser(string $id): ?IdpUser;

    public function findUserByEmail(string $email): ?IdpUser;

    public function findUserByExternalId(string $externalId): ?IdpUser;

    /**
     * One page of the directory, for indexing it in bulk. Fewer than $limit
     * results means the last page.
     *
     * @return array<int, IdpUser>
     *
     * @throws IdpException
     */
    public function listUsers(int $limit, int $offset): array;

    /**
     * Substring search over names, email addresses and ids, for typeahead
     * suggestions. Enforcing a minimum query length is the caller's job.
     *
     * @return array<int, IdpUser>
     *
     * @throws IdpException
     */
    public function searchUsers(string $query, int $limit = 10): array;

    /**
     * @throws IdpException
     */
    public function createUser(array $attributes): IdpUser;

    /**
     * @throws IdpException
     */
    public function updateUser(string $id, array $attributes): IdpUser;

    /**
     * @throws IdpException
     */
    public function updatePassword(string $id, string $password): void;
}
