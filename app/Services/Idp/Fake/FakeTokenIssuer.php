<?php

namespace App\Services\Idp\Fake;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;

/**
 * Mints session tokens for the fake identity provider. Tokens are HS256 JWTs
 * signed with the application key, carrying the same claims GPM relies on
 * from Clerk (iss, azp, sub, sid, iat, nbf, exp).
 */
class FakeTokenIssuer
{
    public const ISSUER = 'fake-idp';

    private readonly string $key;

    public function __construct(
        string $key,
        private readonly string $authorizedParty,
        private readonly int $ttl = 300,
    ) {
        $this->key = self::normaliseKey($key);
    }

    public static function fromConfig(): self
    {
        return new self(
            key: self::signingKey(),
            authorizedParty: (string) config('app.url'),
            ttl: (int) config('idp.fake.token_ttl', 300),
        );
    }

    public function issue(string $subject, array $claims = [], ?int $ttl = null): string
    {
        $now = time();

        $payload = array_merge([
            'iss' => self::ISSUER,
            'azp' => $this->authorizedParty,
            'sub' => $subject,
            'sid' => 'sess_fake_'.Str::lower(Str::random(12)),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($ttl ?? $this->ttl),
        ], $claims);

        return JWT::encode($payload, $this->key, 'HS256');
    }

    public static function signingKey(): string
    {
        $key = (string) config('app.key');

        return Str::startsWith($key, 'base64:') ? base64_decode(substr($key, 7)) : $key;
    }

    /**
     * HS256 needs a key of at least 256 bits; derive one of exactly that
     * size from whatever secret is provided so issuer and verifier agree.
     */
    public static function normaliseKey(string $key): string
    {
        return hash('sha256', $key, true);
    }
}
