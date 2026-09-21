<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use App\Services\Idp\IdpUser;
use App\Modules\User\Models\User;
use Illuminate\Support\Collection;
use App\Providers\IdpServiceProvider;
use App\Services\Idp\IdpUserPayload;
use App\Services\Idp\Contracts\IdpClient;
use App\Services\Idp\Exceptions\IdpException;

/**
 * Bulk-import existing GPM users into the identity provider.
 *
 * For each local user not yet linked, look for an identity the IdP already
 * has (by external_id = person uuid, then by email) and link it; otherwise
 * create one from the user's bcrypt password digest so they keep their
 * password. Idempotent: linked users are skipped, so the command can be
 * re-run to pick up stragglers.
 *
 * A full import indexes the IdP directory once rather than looking each user
 * up, which is the difference between three calls per user and one.
 */
class IdpImportUsers extends Command
{
    protected $signature = 'idp:import-users
        {users?* : User ids or email addresses to import}
        {--all : Import every user that is not yet linked}
        {--dry-run : Report what would happen without writing to the IdP or the database}
        {--throttle=150 : Milliseconds to wait between IdP create calls}
        {--no-prefetch : With --all, look each user up individually instead of indexing the IdP directory first}
        {--no-backfill-external-id : Do not set external_id on existing IdP identities that lack one}';

    protected $description = 'Import existing users into the identity provider, preserving their passwords.';

    /** Directory page size; Clerk's list endpoint caps at 500. */
    private const PAGE_SIZE = 500;

    private int $created = 0;
    private int $linked = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private int $throttleMs = 0;

    /**
     * Existing identities keyed by lowercased email and by external_id, or
     * null when looking each user up individually.
     *
     * @var array{email: array<string, IdpUser>, external: array<string, IdpUser>}|null
     */
    private ?array $index = null;

    public function handle(IdpClient $client): int
    {
        if (! IdpServiceProvider::enabled()) {
            $this->error('No identity provider is configured (IDP_DRIVER=null).');

            return self::FAILURE;
        }

        $users = $this->selectUsers();
        if ($users === null) {
            return self::FAILURE;
        }
        if ($users->isEmpty()) {
            $this->info('No matching users to import.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->throttleMs = max(0, (int) $this->option('throttle'));

        $this->info(($dryRun ? '[dry-run] ' : '')."Importing {$users->count()} user(s) into the ".IdpServiceProvider::driver().' identity provider...');

        // Only worth it in bulk: indexing the directory costs a handful of
        // calls, which a short explicit list would not repay.
        if ($this->option('all') && ! $this->option('no-prefetch')) {
            $this->prefetch($client);
        }

        foreach ($users as $user) {
            try {
                $this->importUser($client, $user, $dryRun);
            } catch (Throwable $e) {
                $this->errors++;
                $this->error("  #{$user->id} {$user->email}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->newLine();
        $this->info("Done. Created: {$this->created}, Linked: {$this->linked}, Skipped: {$this->skipped}, Errors: {$this->errors}");

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resolve the set of users to import, or null on an invalid invocation.
     */
    private function selectUsers(): ?Collection
    {
        $identifiers = array_values(array_filter((array) $this->argument('users')));
        $all = (bool) $this->option('all');

        if (empty($identifiers) && ! $all) {
            $this->error('Specify user ids/emails to import, or pass --all.');

            return null;
        }
        if (! empty($identifiers) && $all) {
            $this->error('Pass either specific users or --all, not both.');

            return null;
        }

        $query = User::query()->with('person');

        if ($all) {
            $query->whereNull('idp_id');
        } else {
            $ids = array_filter($identifiers, 'is_numeric');
            $emails = array_diff($identifiers, $ids);
            $query->where(function ($q) use ($ids, $emails) {
                if (! empty($ids)) {
                    $q->orWhereIn('id', $ids);
                }
                foreach ($emails as $email) {
                    $q->orWhereRaw('LOWER(email) = ?', [mb_strtolower($email)]);
                }
            });
        }

        return $query->orderBy('id')->get();
    }

    /**
     * Index the whole IdP directory up front. Two lookups per user costs
     * thousands of calls on a full import; paging the directory costs one
     * call per 500 identities.
     */
    private function prefetch(IdpClient $client): void
    {
        $byEmail = [];
        $byExternalId = [];
        $total = 0;
        $offset = 0;

        do {
            $page = $this->withRetry(fn () => $client->listUsers(self::PAGE_SIZE, $offset));
            foreach ($page as $idpUser) {
                if ($idpUser->email) {
                    $byEmail[mb_strtolower($idpUser->email)] ??= $idpUser;
                }
                if ($idpUser->externalId) {
                    $byExternalId[$idpUser->externalId] ??= $idpUser;
                }
            }
            $total += count($page);
            $offset += self::PAGE_SIZE;
        } while (count($page) === self::PAGE_SIZE);

        $this->index = ['email' => $byEmail, 'external' => $byExternalId];
        $this->line("  indexed {$total} existing identities in ".(int) ceil($offset / self::PAGE_SIZE).' call(s)');
    }

    private function importUser(IdpClient $client, User $user, bool $dryRun): void
    {
        if ($user->isLinkedToIdp()) {
            $this->line("  #{$user->id} {$user->email}: already linked to {$user->idp_id}, skipping");
            $this->skipped++;

            return;
        }

        $existing = $this->findExisting($client, $user);

        if ($existing) {
            $this->line("  #{$user->id} {$user->email}: linking to existing identity {$existing->id}");
            if (! $dryRun) {
                $this->backfillExternalId($client, $user, $existing);
                $this->link($user, $existing);
            }
            $this->linked++;

            return;
        }

        $this->line("  #{$user->id} {$user->email}: creating identity".($user->password ? ' with password digest' : ' without password'));
        if ($dryRun) {
            $this->created++;

            return;
        }

        $idpUser = $this->withRetry(fn () => $client->createUser(IdpUserPayload::forUser($user)));
        $this->link($user, $idpUser);
        $this->created++;

        if ($this->throttleMs > 0) {
            usleep($this->throttleMs * 1000);
        }
    }

    private function findExisting(IdpClient $client, User $user): ?IdpUser
    {
        $externalId = $user->person?->uuid;

        if ($this->index !== null) {
            return ($externalId ? ($this->index['external'][$externalId] ?? null) : null)
                ?? ($this->index['email'][mb_strtolower($user->email)] ?? null);
        }

        if ($externalId && ($found = $this->withRetry(fn () => $client->findUserByExternalId($externalId)))) {
            return $found;
        }

        return $this->withRetry(fn () => $client->findUserByEmail($user->email));
    }

    private function backfillExternalId(IdpClient $client, User $user, IdpUser $existing): void
    {
        $uuid = $user->person?->uuid;
        if ($this->option('no-backfill-external-id') || ! $uuid || $existing->externalId) {
            return;
        }

        try {
            $this->withRetry(fn () => $client->updateUser($existing->id, ['external_id' => $uuid]));
        } catch (IdpException $e) {
            $this->warn("    could not set external_id on {$existing->id}: {$e->getMessage()}");
        }
    }

    private function link(User $user, IdpUser $idpUser): void
    {
        $user->forceFill([
            'idp_provider' => config('idp.provider_name', 'clerk'),
            'idp_id' => $idpUser->id,
        ])->save();
    }

    /**
     * Retry any IdP call that is rate limited, waiting as long as the provider
     * asked when it sent a Retry-After and backing off exponentially otherwise.
     */
    private function withRetry(callable $call): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return $call();
            } catch (IdpException $e) {
                if (! $e->isRateLimited() || ++$attempt > 5) {
                    throw $e;
                }
                $wait = $e->retryAfter !== null
                    ? $e->retryAfter * 1000
                    : max($this->throttleMs, 250) * (2 ** $attempt);
                $this->warn("    rate limited, backing off {$wait}ms (attempt {$attempt})");
                usleep($wait * 1000);
            }
        }
    }
}
