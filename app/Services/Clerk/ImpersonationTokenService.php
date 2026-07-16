<?php

namespace App\Services\Clerk;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Issues and verifies short-lived, app-signed impersonation tokens.
 *
 * Clerk's own impersonation has very low usage limits, so impersonation is
 * handled locally. When an admin impersonates a user the app mints an HS256
 * JWT carrying the impersonator id (imp_by) and the target user id (sub); a
 * middleware honours it (via the X-Impersonate-Token header) for a session-
 * authenticated request in place of the plain session user. The token is
 * stateless (no DB) and short-lived; "leaving" simply drops it client-side.
 */
class ImpersonationTokenService
{
    private const ALGO = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 1800,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            secret: self::deriveSecret((string) config('app.key')),
            ttl: (int) config('clerk.impersonation_ttl', 1800),
        );
    }

    /**
     * Mint an impersonation token for $targetUserId issued by $impersonatorId.
     */
    public function issue(int $impersonatorId, int $targetUserId): string
    {
        $now = time();

        return JWT::encode([
            'iss' => 'gpm-impersonation',
            'imp_by' => $impersonatorId,
            'sub' => $targetUserId,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ], $this->secret, self::ALGO);
    }

    /**
     * Verify a token, returning ['imp_by' => int, 'sub' => int] or null.
     */
    public function verify(string $token): ?array
    {
        try {
            $claims = (array) JWT::decode($token, new Key($this->secret, self::ALGO));
        } catch (\Throwable $e) {
            return null;
        }

        if (! isset($claims['imp_by'], $claims['sub'])) {
            return null;
        }

        return [
            'imp_by' => (int) $claims['imp_by'],
            'sub' => (int) $claims['sub'],
        ];
    }

    private static function deriveSecret(string $appKey): string
    {
        // Namespace the app key so the impersonation secret can never collide
        // with other uses of APP_KEY (e.g. Laravel encryption).
        $key = str_starts_with($appKey, 'base64:')
            ? base64_decode(substr($appKey, 7))
            : $appKey;

        return hash_hmac('sha256', 'clerk-impersonation', $key);
    }
}
