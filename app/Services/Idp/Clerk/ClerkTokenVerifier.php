<?php

namespace App\Services\Idp\Clerk;

use Throwable;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use RuntimeException;
use Illuminate\Support\Arr;
use App\Services\Idp\IdpIdentity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\Idp\Contracts\TokenVerifier;

/**
 * Verifies Clerk session tokens (RS256 JWTs) against the instance's JWKS.
 *
 * Signing keys are fetched from the Frontend API's JWKS endpoint and cached,
 * so verification is networkless within the cache window. Signature, expiry
 * (exp), not-before (nbf) and issued-at (iat) are validated by the JWT
 * library; the issuer (iss) and authorized-party (azp) claims are checked
 * here and fail closed: Clerk signs tokens for every application on an
 * instance, so iss/azp are what bind a token to this app.
 */
class ClerkTokenVerifier implements TokenVerifier
{
    public const CACHE_KEY = 'idp.clerk.jwks';

    public function __construct(
        private readonly ?string $frontendApiUrl,
        private readonly array $authorizedParties = [],
        private readonly int $jwksCacheTtl = 3600,
        private readonly int $clockSkewLeeway = 60,
        private readonly string $provider = 'clerk',
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            frontendApiUrl: config('idp.clerk.frontend_api_url'),
            authorizedParties: (array) config('idp.clerk.authorized_parties', []),
            jwksCacheTtl: (int) config('idp.clerk.jwks_cache_ttl', 3600),
            clockSkewLeeway: (int) config('idp.clerk.clock_skew_leeway', 60),
            provider: (string) config('idp.provider_name', 'clerk'),
        );
    }

    public function verify(string $token): ?IdpIdentity
    {
        // JWT::$leeway is global to the library, so it is restored afterwards:
        // leaving it set would silently extend the life of every other token
        // decoded in this process.
        $leeway = JWT::$leeway;
        JWT::$leeway = $this->clockSkewLeeway;

        try {
            $keys = JWK::parseKeySet($this->jwks(), 'RS256');
            $claims = (array) JWT::decode($token, $keys);
        } catch (Throwable) {
            return null;
        } finally {
            JWT::$leeway = $leeway;
        }

        if (! $this->issIsAllowed($claims) || ! $this->azpIsAllowed($claims)) {
            return null;
        }

        if (empty($claims['sub']) || ! is_string($claims['sub'])) {
            return null;
        }

        return new IdpIdentity($this->provider, $claims['sub'], $claims);
    }

    /**
     * The issuer must be this instance's Frontend API origin. Without this a
     * token minted by any other Clerk instance would pass, since JWKS lookup
     * only proves Clerk signed it.
     */
    private function issIsAllowed(array $claims): bool
    {
        $expected = $this->normaliseOrigin($this->frontendApiUrl);

        if ($expected === '') {
            return false;
        }

        return $this->normaliseOrigin($claims['iss'] ?? null) === $expected;
    }

    private function azpIsAllowed(array $claims): bool
    {
        // No allow list configured => nothing to enforce.
        if (empty($this->authorizedParties)) {
            return true;
        }

        $azp = $claims['azp'] ?? null;

        // Allow list configured but token carries no azp: reject rather than
        // silently pass, otherwise the allow list is trivially bypassed.
        if (empty($azp) || ! is_string($azp)) {
            return false;
        }

        $azp = $this->normaliseOrigin($azp);
        foreach ($this->authorizedParties as $party) {
            if ($this->normaliseOrigin($party) === $azp) {
                return true;
            }
        }

        return false;
    }

    private function normaliseOrigin(?string $url): string
    {
        return rtrim((string) $url, '/');
    }

    /**
     * Fetch (and cache) the instance's JWKS document.
     */
    private function jwks(): array
    {
        $url = rtrim((string) $this->frontendApiUrl, '/').'/.well-known/jwks.json';

        return Cache::remember(self::CACHE_KEY, $this->jwksCacheTtl, function () use ($url) {
            $jwks = Http::acceptJson()->get($url)->throw()->json();

            if (empty(Arr::get($jwks, 'keys'))) {
                throw new RuntimeException('Clerk JWKS response did not contain any keys.');
            }

            return $jwks;
        });
    }
}
