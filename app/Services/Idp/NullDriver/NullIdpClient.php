<?php

namespace App\Services\Idp\NullDriver;

use App\Services\Idp\IdpUser;
use App\Services\Idp\Contracts\IdpClient;
use App\Services\Idp\Exceptions\IdpException;

/**
 * IdP disabled: lookups find nothing and writes are refused.
 */
class NullIdpClient implements IdpClient
{
    public function getUser(string $id): ?IdpUser
    {
        return null;
    }

    public function findUserByEmail(string $email): ?IdpUser
    {
        return null;
    }

    public function findUserByExternalId(string $externalId): ?IdpUser
    {
        return null;
    }

    public function createUser(array $attributes): IdpUser
    {
        throw new IdpException('No identity provider is configured (IDP_DRIVER=null).');
    }

    public function updateUser(string $id, array $attributes): IdpUser
    {
        throw new IdpException('No identity provider is configured (IDP_DRIVER=null).');
    }

    public function updatePassword(string $id, string $password): void
    {
        throw new IdpException('No identity provider is configured (IDP_DRIVER=null).');
    }
}
