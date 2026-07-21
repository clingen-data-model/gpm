<?php

namespace App\Console\Commands\Dev;

use App\Services\Clerk\ClerkClientFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportConsortiumIdentitiesToClerk extends Command
{
    protected $signature = 'consortium-identities:import-clerk
        {batch_uuid : The import_batch_uuid to process}
        {--limit=25 : Max number of candidate rows to process}
        {--ids= : Optional comma-separated candidate IDs to process}
        {--force : Re-import rows even if clerk_user_id already exists}
        {--dry-run : Show what would be created without changing Clerk or staging}';

    protected $description = 'Create or link Clerk users from resolved consortium identity candidates and store Clerk user IDs back into staging.';

    public function __construct(private ClerkClientFactory $clerkClientFactory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $batchUuid = $this->argument('batch_uuid');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = DB::table('consortium_identity_candidates')
            ->where('import_batch_uuid', $batchUuid)
            ->whereNotNull('resolved_gpm_uuid')
            ->whereNotNull('canonical_email')
            ->whereIn('recommended_action', ['link_existing_gpm', 'create_new_gpm_uuid'])
            ->orderBy('id');

        if ($this->option('ids')) {
            $ids = collect(explode(',', $this->option('ids')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->values()
                ->all();

            $query->whereIn('id', $ids);
        } else {
            if (!$force) {
                $query->whereNull('clerk_user_id');
            }

            $query->limit($limit);
        }

        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            $this->info('No matching candidate rows found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$candidates->count()} candidate row(s).");

        if ($dryRun) {
            $this->warn('[dry-run] No Clerk users will be created/updated and staging will not be changed.');
        }

        $client = $this->clerkClientFactory->make();

        $created = 0;
        $linked = 0;
        $errors = 0;

        foreach ($candidates as $candidate) {
            try {
                $payload = $this->buildClerkPayload($candidate);

                if (empty($candidate->resolved_gpm_uuid)) {
                    throw new \RuntimeException("Candidate #{$candidate->id} has no resolved_gpm_uuid. Run consortium-identities:resolve-export first.");
                }

                $existingClerkUser = $this->findExistingClerkUser(client: $client, externalId: $candidate->resolved_gpm_uuid, email: $candidate->canonical_email);
                if ($dryRun) {
                    if ($existingClerkUser) {
                        $this->line("Would link candidate #{$candidate->id} {$candidate->canonical_email} to existing Clerk user ".data_get($existingClerkUser, 'id'));
                    } else {
                        $this->line("Would create candidate #{$candidate->id} {$candidate->canonical_email}");
                        $this->line(json_encode($payload, JSON_PRETTY_PRINT));
                    }
                    continue;
                }

                if ($existingClerkUser) {
                    $result = $this->linkExistingClerkUser($client, $candidate, $existingClerkUser);
                    if ($result === 'linked') {
                        $linked++;
                    } else {
                        $errors++;
                    }
                    continue;
                }
                $this->assertExistingGpmPersonCanBeLinked($candidate);
                $response = $client->post('/users', $payload);

                if ($response->failed()) {
                    // Fallback: if create failed because the email/external_id already exists, we'll link it instead.
                    $fallbackUser = $this->findExistingClerkUser(client: $client,  externalId: $candidate->resolved_gpm_uuid, email: $candidate->canonical_email);
                    if ($fallbackUser) {
                        $result = $this->linkExistingClerkUser($client, $candidate, $fallbackUser);
                        if ($result === 'linked') {
                            $linked++;
                        } else {
                            $errors++;
                        }
                        continue;
                    }

                    $response->throw();
                }

                $clerkUser = $response->json();
                $clerkUserId = data_get($clerkUser, 'id');

                if (!$clerkUserId) {
                    throw new \RuntimeException('Clerk create response did not include user id.');
                }                

                DB::transaction(function () use ($candidate, $clerkUserId, $clerkUser) {
                    DB::table('consortium_identity_candidates')
                        ->where('id', $candidate->id)
                        ->update([
                            'clerk_user_id' => $clerkUserId,
                            'clerk_import_status' => 'imported',
                            'clerk_import_error' => null,
                            'clerk_imported_at' => now(),
                            'clerk_response' => json_encode($clerkUser),
                            'updated_at' => now(),
                        ]);
                    $this->syncExistingGpmPerson($candidate, $clerkUserId);
                });
                $this->info("Created candidate #{$candidate->id}: {$candidate->canonical_email} → {$clerkUserId}");
                $created++;
            } catch (Throwable $e) {
                $this->error("Failed candidate #{$candidate->id}: {$e->getMessage()}");

                DB::table('consortium_identity_candidates')
                    ->where('id', $candidate->id)
                    ->update([
                        'clerk_import_status' => 'error',
                        'clerk_import_error' => $e->getMessage(),
                        'updated_at' => now(),
                    ]);

                $errors++;
            }
        }

        $this->newLine();
        $this->table(
            ['Outcome', 'Count'],
            [
                ['Created', $created],
                ['Linked existing Clerk user', $linked],
                ['Errors', $errors],
            ]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function buildClerkPayload(object $candidate): array
    {
        $payload = [
            'external_id' => $candidate->resolved_gpm_uuid,
            'email_address' => [$this->normalizeEmail($candidate->canonical_email)],
            'private_metadata' => $this->privateMetadata($candidate),
        ];

        if (!empty($candidate->canonical_first_name)) {
            $payload['first_name'] = $candidate->canonical_first_name;
        }

        if (!empty($candidate->canonical_last_name)) {
            $payload['last_name'] = $candidate->canonical_last_name;
        }

        if (!empty($candidate->password_digest)) {
            $payload['password_digest'] = $candidate->password_digest;
            $payload['password_hasher'] = $candidate->password_hasher ?: 'bcrypt';
            $payload['skip_password_checks'] = true;
        }

        return $payload;
    }

    protected function findExistingClerkUser($client, string $externalId, ?string $email): ?array
    {
        $externalIdResponse = $client->get('/users', [
            'external_id' => [$externalId],
            'limit' => 1,
        ]);

        $externalIdResponse->throw();

        $externalIdMatches = data_get($externalIdResponse->json(), 'data', []);

        if (!empty($externalIdMatches)) {
            return $externalIdMatches[0];
        }

        $email = $this->normalizeEmail($email);

        if (!$email) {
            return null;
        }

        $emailResponse = $client->get('/users', [
            'email_address' => [$email],
            'limit' => 1,
        ]);

        $emailResponse->throw();

        $emailMatches = data_get($emailResponse->json(), 'data', []);

        return !empty($emailMatches) ? $emailMatches[0] : null;
    }

    protected function linkExistingClerkUser($client, object $candidate, array $existingClerkUser): string
    {
        $clerkUserId = data_get($existingClerkUser, 'id');

        if (!$clerkUserId) {
            throw new \RuntimeException('Existing Clerk user did not include an id.');
        }

        $existingExternalId = data_get($existingClerkUser, 'external_id');

        if ($existingExternalId && $existingExternalId !== $candidate->resolved_gpm_uuid) {
            $message = "Existing Clerk user {$clerkUserId} has different external_id {$existingExternalId}.";

            DB::table('consortium_identity_candidates')
                ->where('id', $candidate->id)
                ->update([
                    'clerk_import_status' => 'manual_review_clerk_conflict',
                    'clerk_import_error' => $message,
                    'clerk_response' => json_encode($existingClerkUser),
                    'updated_at' => now(),
                ]);

            $this->warn("Manual review candidate #{$candidate->id}: {$message}");

            return 'manual_review';
        }

        if (!$existingExternalId) {
            $client->patch("/users/{$clerkUserId}", ['external_id' => $candidate->resolved_gpm_uuid,])->throw();
        }

        // Metadata update uses the dedicated metadata endpoint.
        // Clerk updateUserMetadata deep-merges metadata, unlike replaceUserMetadata.
        $client->patch("/users/{$clerkUserId}/metadata", ['private_metadata' => $this->privateMetadata($candidate)])->throw();

        DB::transaction(function () use ($candidate, $clerkUserId, $existingClerkUser) {
            DB::table('consortium_identity_candidates')
                ->where('id', $candidate->id)
                ->update([
                    'clerk_user_id' => $clerkUserId,
                    'clerk_import_status' => 'linked_existing_clerk',
                    'clerk_import_error' => null,
                    'clerk_imported_at' => now(),
                    'clerk_response' => json_encode($existingClerkUser),
                    'updated_at' => now(),
                ]);

            $this->syncExistingGpmPerson($candidate, $clerkUserId);
        });

        $this->info("Linked candidate #{$candidate->id}: {$candidate->canonical_email} → {$clerkUserId}");

        return 'linked';
    }

    protected function privateMetadata(object $candidate): array
    {
        return [
            'applications' => $this->applicationsFromSourceSystems($candidate->source_systems),
        ];
    }

    protected function applicationsFromSourceSystems(?string $sourceSystemsJson): array
    {
        $sourceSystems = json_decode($sourceSystemsJson ?: '[]', true);

        if (!is_array($sourceSystems)) {
            return [];
        }

        return collect($sourceSystems)
            ->map(fn ($system) => match (strtoupper((string) $system)) {
                'GPM' => 'GPM',
                'GT' => 'GeneTracker',
                default => (string) $system,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = trim(mb_strtolower($email));

        return $email === '' ? null : $email;
    }

    protected function syncExistingGpmPerson(object $candidate, string $clerkUserId): void
    {
        if ($candidate->recommended_action !== 'link_existing_gpm') {
            return;
        }

        if (empty($candidate->resolved_gpm_uuid)) {
            throw new \RuntimeException("Candidate #{$candidate->id} has no resolved_gpm_uuid.");
        }

        $person = DB::table('people')->where('uuid', $candidate->resolved_gpm_uuid)->first();

        if (!$person) {
            throw new \RuntimeException("No GPM person found for UUID {$candidate->resolved_gpm_uuid}.");
        }

        if (!empty($person->clerk_user_id) && $person->clerk_user_id !== $clerkUserId) {
            throw new \RuntimeException("Person {$person->id} already has a different clerk_user_id on people table.");
        }

        DB::table('people')->where('id', $person->id)->update(['clerk_user_id' => $clerkUserId, 'updated_at' => now()]);
    }

    protected function assertExistingGpmPersonCanBeLinked(object $candidate): void
    {
        if ($candidate->recommended_action !== 'link_existing_gpm') {
            return;
        }

        $person = DB::table('people')
            ->where('uuid', $candidate->resolved_gpm_uuid)
            ->first();

        if (!$person) {
            throw new \RuntimeException("No GPM person found for UUID {$candidate->resolved_gpm_uuid}.");
        }

        if (!empty($person->clerk_user_id)) {
            throw new \RuntimeException("Person {$person->id} already has clerk_user_id {$person->clerk_user_id}. Clear it before creating/linking another Clerk user.");
        }
    }
}