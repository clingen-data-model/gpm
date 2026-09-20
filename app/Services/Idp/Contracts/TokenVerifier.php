<?php

namespace App\Services\Idp\Contracts;

use App\Services\Idp\IdpIdentity;

interface TokenVerifier
{
    /**
     * Verify a raw session token and return the identity it carries, or null
     * when the token is invalid for any reason (signature, expiry, issuer,
     * authorized party, ...). Implementations must never throw on bad input.
     */
    public function verify(string $token): ?IdpIdentity;
}
