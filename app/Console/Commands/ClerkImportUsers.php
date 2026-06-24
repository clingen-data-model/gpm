<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Modules\User\Models\User;
use App\Services\Clerk\ClerkApiClient;

/**
 * Bulk-import existing GPM users into Clerk.
 *
 * For each local user without a clerk_id, this checks Clerk for an existing
 * identity with the same email (other ClinGen systems share the workspace, so
 * one may already exist). If found, the local account is linked. Otherwise a
 * Clerk identity is created from the user's existing bcrypt hash so the user
 * keeps their password. The local clerk_id column records the linkage.
 *
 * Selection is explicit: pass user ids/emails, or --all for every unlinked
 * user. The command is idempotent (users that already have a clerk_id are
 * skipped) and rate-limit aware.
 */
class ClerkImportUsers extends Command
{
    protected $signature = 'clerk:import-users
        {users?* : User ids or email addresses to import}
        {--all : Import every user that is not yet linked to Clerk}
        {--dry-run : Report what would happen without writing to Clerk or the database}
        {--throttle=150 : Milliseconds to wait between Clerk create calls}';

    protected $description = 'Import existing users into Clerk, preserving their bcrypt passwords.';

    private int $created = 0;
    private int $linked = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(ClerkApiClient $clerk): int
    {
        $users = $this->selectUsers();

        if ($users === null) {
            return self::FAILURE;
        }

        if ($users->isEmpty()) {
            $this->info('No matching users to import.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $throttleMs = (int) $this->option('throttle');

        $this->info(($dryRun ? '[dry-run] ' : '')."Importing {$users->count()} user(s) into Clerk...");

        foreach ($users as $user) {
            try {
                $this->importUser($clerk, $user, $dryRun, $throttleMs);
            } catch (\Throwable $e) {
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
        $identifiers = (array) $this->argument('users');
        $all = (bool) $this->option('all');

        if (empty($identifiers) && ! $all) {
            $this->error('Specify user ids/emails to import, or pass --all.');
            return null;
        }

        if (! empty($identifiers) && $all) {
            $this->error('Pass either specific users or --all, not both.');
            return null;
        }

        $query = User::query()->whereNull('clerk_id');

        if (! $all) {
            $ids = array_filter($identifiers, 'is_numeric');
            $emails = array_diff($identifiers, $ids);

            $query->where(function ($q) use ($ids, $emails) {
                if (! empty($ids)) {
                    $q->orWhereIn('id', $ids);
                }
                foreach ($emails as $email) {
                    $q->orWhereRaw('LOWER(email) = ?', [strtolower($email)]);
                }
            });
        }

        return $query->orderBy('id')->get();
    }

    private function importUser(ClerkApiClient $clerk, User $user, bool $dryRun, int $throttleMs): void
    {
        $existingClerkId = $clerk->findUserIdByEmail($user->email);

        if ($existingClerkId) {
            $this->line("  #{$user->id} {$user->email}: linking to existing Clerk user {$existingClerkId}");
            if (! $dryRun) {
                $user->forceFill(['clerk_id' => $existingClerkId])->save();
            }
            $this->linked++;
            return;
        }

        $this->line("  #{$user->id} {$user->email}: creating Clerk user");

        if ($dryRun) {
            $this->created++;
            return;
        }

        $response = $this->createWithRetry($clerk, $this->payloadFor($user), $throttleMs);
        $clerkId = $response->json('id');

        if (! $clerkId) {
            throw new \RuntimeException('Clerk create returned no id: '.$response->body());
        }

        $user->forceFill(['clerk_id' => $clerkId])->save();
        $this->created++;

        if ($throttleMs > 0) {
            usleep($throttleMs * 1000);
        }
    }

    /**
     * Build the Clerk create-user payload, preserving the bcrypt password when
     * the user has one. Clerk accepts Laravel's $2y$ bcrypt hashes directly.
     */
    private function payloadFor(User $user): array
    {
        [$firstName, $lastName] = $this->splitName($user->name);

        $payload = [
            'email_address' => [$user->email],
            'external_id' => (string) $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'skip_password_checks' => true,
        ];

        if (! empty($user->password)) {
            $payload['password_digest'] = $user->password;
            $payload['password_hasher'] = 'bcrypt';
        } else {
            // No local password (e.g. invite never completed): create without
            // one; the user sets a password through Clerk on first sign-in.
            $payload['skip_password_requirement'] = true;
        }

        return $payload;
    }

    /**
     * POST with a small exponential backoff on HTTP 429 (rate limited).
     */
    private function createWithRetry(ClerkApiClient $clerk, array $payload, int $throttleMs)
    {
        $attempt = 0;

        while (true) {
            $response = $clerk->createUser($payload);

            if ($response->status() !== 429) {
                return $response->throw();
            }

            $attempt++;
            if ($attempt > 5) {
                $response->throw();
            }

            $wait = max($throttleMs, 250) * (2 ** $attempt);
            $this->warn("  rate limited, backing off {$wait}ms (attempt {$attempt})");
            usleep($wait * 1000);
        }
    }

    /**
     * @return array{0: string, 1: string} [firstName, lastName]
     */
    private function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
