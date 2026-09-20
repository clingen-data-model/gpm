<?php

namespace App\Services\Idp;

/**
 * Provider-neutral view of a user record at the identity provider.
 */
final class IdpUser
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $email,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $externalId = null,
        public readonly array $raw = [],
    ) {
    }

    /**
     * Full name when both parts are known, otherwise null.
     */
    public function name(): ?string
    {
        $name = trim(($this->firstName ?? '').' '.($this->lastName ?? ''));

        return $name === '' ? null : $name;
    }

    /**
     * Build from a Clerk Backend API user object.
     */
    public static function fromClerk(array $data): self
    {
        $primaryId = $data['primary_email_address_id'] ?? null;
        $email = null;
        foreach ($data['email_addresses'] ?? [] as $address) {
            if ($primaryId === null || ($address['id'] ?? null) === $primaryId) {
                $email = $address['email_address'] ?? null;
                break;
            }
        }

        return new self(
            id: (string) $data['id'],
            email: $email,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            externalId: $data['external_id'] ?? null,
            raw: $data,
        );
    }
}
