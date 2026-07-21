<?php

namespace App\Console\Commands\Dev;

use App\Services\Clerk\ClerkClientFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteClerkImportedUsers extends Command
{
    protected $signature = 'consortium-identities:delete-clerk-users
        {batch_uuid}
        {--limit=25}
        {--status=imported}
        {--ids=}
        {--dry-run}
        {--reset-staging}';

    protected $description = 'Delete Clerk users created from a consortium identity import batch';

    public function __construct(private ClerkClientFactory $clerkClientFactory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $batchUuid = $this->argument('batch_uuid');
        $limit = (int) $this->option('limit');
        $statuses = collect(explode(',', $this->option('status')))
            ->map(fn ($status) => trim($status))
            ->filter()
            ->values();

        $query = DB::table('consortium_identity_candidates')
            ->where('import_batch_uuid', $batchUuid)
            ->whereNotNull('clerk_user_id')
            ->whereIn('clerk_import_status', $statuses)
            ->orderBy('id');

        if ($this->option('ids')) {
            $ids = collect(explode(',', $this->option('ids')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->values();

            $query->whereIn('id', $ids);
        } else {
            $query->limit($limit);
        }

        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            $this->info('No matching Clerk users found for this batch.');
            return self::SUCCESS;
        }

        $this->info("Found {$candidates->count()} Clerk user(s).");

        // Use the same factory method name as your import command.
        $client = $this->clerkClientFactory->make();

        foreach ($candidates as $candidate) {
            $clerkUserId = $candidate->clerk_user_id;
            $email = $candidate->canonical_email;

            if ($this->option('dry-run')) {
                $this->line("Would delete candidate #{$candidate->id}: {$email} / {$clerkUserId}");
                continue;
            }

            try {
                $response = $client->delete("/users/{$clerkUserId}");

                if ($response->status() === 404) {
                    $this->warn("Already missing in Clerk: candidate #{$candidate->id} / {$clerkUserId}");
                    $this->markDeletedOrReset($candidate->id, ['status' => 404, 'message' => 'Already missing in Clerk']);
                    continue;
                }

                $response->throw();

                $this->info("Deleted candidate #{$candidate->id}: {$email} / {$clerkUserId}");

                $this->markDeletedOrReset($candidate->id, $response->json());
            } catch (Throwable $e) {
                $this->error("Failed candidate #{$candidate->id}: {$e->getMessage()}");

                DB::table('consortium_identity_candidates')
                    ->where('id', $candidate->id)
                    ->update([
                        'clerk_import_error' => $e->getMessage(),
                        'updated_at' => now(),
                    ]);
            }
        }

        return self::SUCCESS;
    }

    protected function markDeletedOrReset(int $candidateId, array $response): void
    {
        if ($this->option('reset-staging')) {
            DB::table('consortium_identity_candidates')
                ->where('id', $candidateId)
                ->update([
                    'clerk_user_id' => null,
                    'clerk_import_status' => null,
                    'clerk_import_error' => null,
                    'clerk_imported_at' => null,
                    'clerk_response' => null,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('consortium_identity_candidates')
            ->where('id', $candidateId)
            ->update([
                'clerk_user_id' => null,
                'clerk_import_status' => 'deleted_from_clerk',
                'clerk_import_error' => null,
                'clerk_response' => json_encode($response),
                'updated_at' => now(),
            ]);
    }
}