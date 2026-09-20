<?php

namespace App\Services\Idp;

/**
 * The verified identity carried by an IdP session token.
 */
final class IdpIdentity
{
    public function __construct(
        public readonly string $provider,
        public readonly string $subject,
        public readonly array $claims = [],
    ) {
    }

    public function claim(string $name, mixed $default = null): mixed
    {
        return $this->claims[$name] ?? $default;
    }

    /**
     * Email carried in the token, if the provider was configured to include it.
     */
    public function email(): ?string
    {
        $email = $this->claim('email');

        return is_string($email) && $email !== '' ? $email : null;
    }
}
