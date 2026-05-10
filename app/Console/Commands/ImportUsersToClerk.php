<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Clerk\Backend\ClerkBackend;
use Clerk\Backend\Models\Operations\CreateUserRequestBody;
use Clerk\Backend\Models\Operations\GetUserListRequest;

class ImportUsersToClerk extends Command
{
    protected $signature = 'clerk:import-users
                            {users?* : Optional user selectors (email, id, or uuid)}
                            {--dry-run : Show what would be done without making any changes}
                            {--skip-linked : Skip users that already have a clerk_user_id}';

    protected $description = 'Import existing GPM users into Clerk, migrating their bcrypt password hashes.';

    public function handle(): int
    {
        $secretKey = config('clerk.secret_key');
        if (! $secretKey) {
            $this->error('CLERK_SECRET_KEY is not configured.');
            return 1;
        }

        $clerk = ClerkBackend::builder()->setSecurity($secretKey)->build();
        $userSelectors = (array) $this->argument('users');
        $dryRun = $this->option('dry-run');
        $skipLinked = $this->option('skip-linked');

        if ($dryRun) {
            $this->info('[dry-run] No changes will be made.');
        }

        $query = User::query();

        if (! empty($userSelectors)) {
            $query->where(function ($builder) use ($userSelectors) {
                $builder->whereIn('email', $userSelectors)
                    ->orWhereIn('id', $userSelectors);
            });
        }

        if ($skipLinked) {
            $query->whereNull('clerk_user_id');
        }

        $users = $query->get();
        $this->info("Processing {$users->count()} users...");

        $imported = 0;
        $linked = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($users as $user) {
            $result = $this->processUser($clerk, $user, $dryRun);
            match ($result) {
                'imported' => $imported++,
                'linked'   => $linked++,
                'skipped'  => $skipped++,
                'error'    => $errors++,
            };
        }

        $this->newLine();
        $this->table(
            ['Outcome', 'Count'],
            [
                ['Imported (new Clerk account)', $imported],
                ['Linked (existing Clerk account)', $linked],
                ['Skipped (already linked)', $skipped],
                ['Errors', $errors],
            ]
        );

        return $errors > 0 ? 1 : 0;
    }

    private function processUser(ClerkBackend $clerk, User $user, bool $dryRun): string
    {
        if ($user->clerk_user_id) {
            $this->line("  <comment>skip</comment>  {$user->email} (already linked: {$user->clerk_user_id})");
            return 'skipped';
        }

        // Check if a Clerk account already exists for this email.
        $existing = $clerk->users->list(new GetUserListRequest(emailAddress: [$user->email]));
        $existingUsers = $existing->userList ?? [];

        if (count($existingUsers) > 0) {
            $clerkUserId = $existingUsers[0]->id;
            $this->line("  <info>link</info>   {$user->email} → {$clerkUserId}");
            if (! $dryRun) {
                $user->update(['clerk_user_id' => $clerkUserId]);
            }
            return 'linked';
        }

        // Create a new Clerk user, passing the bcrypt hash so the user can
        // log in with their existing password without a reset.
        $nameParts = explode(' ', $user->name, 2);

        $this->line("  <info>create</info> {$user->email}");

        if (! $dryRun) {
            try {
                $response = $clerk->users->create(new CreateUserRequestBody(
                    emailAddress: [$user->email],
                    passwordHasher: 'bcrypt',
                    passwordDigest: $user->password,
                    firstName: $nameParts[0] ?? null,
                    lastName: $nameParts[1] ?? null,
                    skipPasswordChecks: true,
                ));

                $clerkUserId = $response->user?->id;
                if ($clerkUserId) {
                    $user->update(['clerk_user_id' => $clerkUserId]);
                } else {
                    $this->error("  Failed to get Clerk user ID for {$user->email}");
                    return 'error';
                }
            } catch (\Throwable $e) {
                $this->error("  Error importing {$user->email}: {$e->getMessage()}");
                return 'error';
            }
        }

        return 'imported';
    }
}
