<?php

namespace App\Services\Idp;

use App\Modules\User\Models\User;

/**
 * Builds the provider-neutral attributes used to create a GPM user's
 * identity at the IdP. Shared by the mirror-on-create path and the bulk
 * import so both produce the same record.
 */
final class IdpUserPayload
{
    public static function forUser(User $user, ?string $plainPassword = null): array
    {
        [$firstName, $lastName] = self::names($user);

        $payload = [
            'email' => $user->email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'external_id' => $user->person?->uuid,
            'created_at' => $user->created_at?->toRfc3339String(),
        ];

        if ($plainPassword !== null && $plainPassword !== '') {
            $payload['password'] = $plainPassword;
        } elseif ($user->password) {
            // Laravel stores bcrypt ($2y$) hashes, which Clerk imports as-is.
            $payload['password_digest'] = $user->password;
            $payload['password_hasher'] = 'bcrypt';
        }

        return $payload;
    }

    /**
     * Prefer the Person's structured name; fall back to splitting users.name.
     */
    private static function names(User $user): array
    {
        if ($user->person && ($user->person->first_name || $user->person->last_name)) {
            return [$user->person->first_name ?: null, $user->person->last_name ?: null];
        }

        $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];

        return [$parts[0] ?? null, $parts[1] ?? null];
    }
}
