<?php

namespace App\Services\Idp\Fake;

use Illuminate\Support\Str;

/**
 * User directory of the fake identity provider.
 *
 * Backed by a JSON file when a path is configured (local development, so
 * identities survive container restarts) or held in memory otherwise (tests).
 * Records mirror the provider-neutral attribute names used by IdpClient.
 */
class FakeIdpStore
{
    /** @var array<string, array> */
    private array $users = [];

    private bool $loaded = false;

    public function __construct(private readonly ?string $path = null)
    {
    }

    /** @return array<string, array> */
    public function all(): array
    {
        $this->load();

        return $this->users;
    }

    public function find(string $id): ?array
    {
        $this->load();

        return $this->users[$id] ?? null;
    }

    /**
     * Match the primary address or any address listed under `emails`.
     */
    public function findByEmail(string $email): ?array
    {
        $needle = Str::lower(trim($email));
        foreach ($this->all() as $user) {
            if (in_array($needle, self::emailsOf($user), true)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Every address on a record, lower-cased, primary first.
     *
     * @return array<int, string>
     */
    public static function emailsOf(array $user): array
    {
        $emails = [];
        foreach ([$user['email'] ?? null, ...($user['emails'] ?? [])] as $address) {
            $address = Str::lower(trim((string) $address));
            if ($address !== '' && ! in_array($address, $emails, true)) {
                $emails[] = $address;
            }
        }

        return $emails;
    }

    public function findByExternalId(string $externalId): ?array
    {
        foreach ($this->all() as $user) {
            if (($user['external_id'] ?? null) === $externalId) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Insert or replace a record. A missing id is generated.
     */
    public function put(array $user): array
    {
        $this->load();
        $user['id'] = $user['id'] ?? $this->nextId();
        $this->users[$user['id']] = $user;
        $this->persist();

        return $user;
    }

    public function forget(string $id): void
    {
        $this->load();
        unset($this->users[$id]);
        $this->persist();
    }

    public function reset(): void
    {
        $this->users = [];
        $this->loaded = true;
        $this->persist();
    }

    public function nextId(): string
    {
        return 'user_fake_'.Str::lower(Str::random(12));
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        if ($this->path && is_file($this->path)) {
            $decoded = json_decode((string) file_get_contents($this->path), true);
            $this->users = is_array($decoded) ? $decoded : [];
        }
    }

    private function persist(): void
    {
        if (! $this->path) {
            return;
        }

        $dir = dirname($this->path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($this->path, json_encode($this->users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
