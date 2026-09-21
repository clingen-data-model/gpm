<?php

namespace App\Services\Idp;

use Illuminate\Support\Str;

/**
 * Provider-neutral view of a user record at the identity provider.
 */
final class IdpUser
{
    /**
     * @param  array<int, string>  $emails  Every usable address, lower-cased, unique, primary first.
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $email,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $externalId = null,
        public readonly array $raw = [],
        public readonly array $emails = [],
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
     * Whether the address is one of the identity's usable addresses (case-insensitive).
     */
    public function hasEmail(string $email): bool
    {
        return in_array(Str::lower(trim($email)), $this->emails, true);
    }

    /**
     * Lower-cased, de-duplicated address list with the primary first.
     *
     * @param  iterable<int, string|null>  $others
     * @return array<int, string>
     */
    public static function normalizeEmails(?string $primary, iterable $others = []): array
    {
        $emails = [];
        foreach ([$primary, ...$others] as $address) {
            $address = Str::lower(trim((string) $address));
            if ($address !== '' && ! in_array($address, $emails, true)) {
                $emails[] = $address;
            }
        }

        return $emails;
    }

    /**
     * Build from a Clerk Backend API user object.
     *
     * The primary address is always usable; other addresses only when Clerk
     * has verified them, so an unverified claim on someone else's address can
     * never be selected for a GPM account.
     */
    public static function fromClerk(array $data): self
    {
        $primaryId = $data['primary_email_address_id'] ?? null;
        $addresses = $data['email_addresses'] ?? [];

        $email = null;
        foreach ($addresses as $address) {
            if ($primaryId === null || ($address['id'] ?? null) === $primaryId) {
                $email = $address['email_address'] ?? null;
                break;
            }
        }

        $verified = [];
        foreach ($addresses as $address) {
            if (($address['verification']['status'] ?? null) === 'verified') {
                $verified[] = $address['email_address'] ?? null;
            }
        }

        return new self(
            id: (string) $data['id'],
            email: $email,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            externalId: $data['external_id'] ?? null,
            raw: $data,
            emails: self::normalizeEmails($email, $verified),
        );
    }
}
