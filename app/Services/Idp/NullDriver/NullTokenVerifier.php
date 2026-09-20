<?php

namespace App\Services\Idp\NullDriver;

use App\Services\Idp\IdpIdentity;
use App\Services\Idp\Contracts\TokenVerifier;

/**
 * IdP sign-in disabled: no token is ever valid.
 */
class NullTokenVerifier implements TokenVerifier
{
    public function verify(string $token): ?IdpIdentity
    {
        return null;
    }
}
