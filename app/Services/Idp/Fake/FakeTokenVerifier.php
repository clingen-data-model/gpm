<?php

namespace App\Services\Idp\Fake;

use Throwable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Services\Idp\IdpIdentity;
use App\Services\Idp\Contracts\TokenVerifier;

/**
 * Verifies tokens minted by FakeTokenIssuer. Mirrors the checks the Clerk
 * verifier performs (signature, exp/nbf, issuer, authorized party).
 */
class FakeTokenVerifier implements TokenVerifier
{
    private readonly string $key;

    public function __construct(
        string $key,
        private readonly string $authorizedParty,
        private readonly string $provider,
    ) {
        $this->key = FakeTokenIssuer::normaliseKey($key);
    }

    public static function fromConfig(): self
    {
        return new self(
            key: FakeTokenIssuer::signingKey(),
            authorizedParty: (string) config('app.url'),
            provider: (string) config('idp.provider_name', 'clerk'),
        );
    }

    public function verify(string $token): ?IdpIdentity
    {
        try {
            $claims = (array) JWT::decode($token, new Key($this->key, 'HS256'));
        } catch (Throwable) {
            return null;
        }

        if (($claims['iss'] ?? null) !== FakeTokenIssuer::ISSUER) {
            return null;
        }
        if (($claims['azp'] ?? null) !== $this->authorizedParty) {
            return null;
        }
        if (empty($claims['sub']) || ! is_string($claims['sub'])) {
            return null;
        }

        return new IdpIdentity($this->provider, $claims['sub'], $claims);
    }
}
