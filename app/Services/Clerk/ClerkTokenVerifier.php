<?php

namespace App\Services\Clerk;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Verifies Clerk session tokens (RS256 JWTs) without an official SDK.
 *
 * Signing keys are pulled from the instance's Frontend API JWKS endpoint and
 * cached, so verification is networkless within the cache window. Signature,
 * expiry (exp), not-before (nbf) and issued-at (iat) are validated by the JWT
 * library; the issuer (iss) and authorized-party (azp) claims are checked here.
 *
 * Both claim checks fail closed: a token whose iss does not match this
 * instance, or which carries no azp while an allow list is configured, is
 * rejected. Signature validity alone is not sufficient — Clerk signs for every
 * application on the instance, so iss/azp are what bind a token to this app.
 */
class ClerkTokenVerifier
{
    public function __construct(
        private readonly ?string $frontendApiUrl = null,
        private readonly array $authorizedParties = [],
        private readonly int $jwksCacheTtl = 3600,
        private readonly int $clockSkewLeeway = 60,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            frontendApiUrl: config('clerk.frontend_api_url'),
            authorizedParties: config('clerk.authorized_parties', []),
            jwksCacheTtl: (int) config('clerk.jwks_cache_ttl', 3600),
            clockSkewLeeway: (int) config('clerk.clock_skew_leeway', 60),
        );
    }

    /**
     * Verify a raw bearer token and return its claims, or null if invalid.
     */
    public function verify(string $token): ?array
    {
        // JWT::$leeway is global to the library, so it is restored afterwards:
        // leaving it set would silently extend the life of every other token
        // decoded in this process (notably impersonation tokens).
        $leeway = JWT::$leeway;
        JWT::$leeway = $this->clockSkewLeeway;

        try {
            $keys = JWK::parseKeySet($this->jwks());
            $claims = (array) JWT::decode($token, $keys);
        } catch (\Throwable $e) {
            return null;
        } finally {
            JWT::$leeway = $leeway;
        }

        if (! $this->issIsAllowed($claims) || ! $this->azpIsAllowed($claims)) {
            return null;
        }

        return $claims;
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
        if (empty($azp)) {
            return false;
        }

        return in_array($azp, $this->authorizedParties, true);
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

        return Cache::remember('clerk.jwks', $this->jwksCacheTtl, function () use ($url) {
            $response = Http::acceptJson()->get($url)->throw();

            $jwks = $response->json();

            if (empty(Arr::get($jwks, 'keys'))) {
                throw new \RuntimeException('Clerk JWKS response did not contain any keys.');
            }

            return $jwks;
        });
    }
}
