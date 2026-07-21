<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsolidateConsortiumIdentityImport extends Command
{
    protected $signature = 'consortium-identities:consolidate
        {batch_uuid : The import_batch_uuid to consolidate}
        {--truncate : Delete existing candidate rows for this batch before rebuilding}';

    protected $description = 'Normalize and consolidate consortium identity import rows into review candidates.';

    public function handle(): int
    {
        $batchUuid = $this->argument('batch_uuid');

        if ($this->option('truncate')) {
            DB::table('consortium_identity_candidates')
                ->where('import_batch_uuid', $batchUuid)
                ->delete();

            $this->info("Deleted existing candidate rows for batch {$batchUuid}.");
        }

        $rows = DB::table('consortium_identity_import_rows')
            ->where('import_batch_uuid', $batchUuid)
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $row->email_normalized = $this->normalizeEmail($row->email);

                [$firstName, $lastName] = $this->splitName($row->full_name);
                $row->first_name_normalized = $this->normalizeNamePart($firstName);
                $row->last_name_normalized = $this->normalizeNamePart($lastName);

                return $row;
            });

        if ($rows->isEmpty()) {
            $this->warn("No import rows found for batch {$batchUuid}.");
            return self::SUCCESS;
        }

        $this->info("Loaded {$rows->count()} raw import rows.");

        $candidateRows = [];

        $rowsWithEmail = $rows->filter(fn ($row) => !empty($row->email_normalized));
        $rowsWithoutEmail = $rows->reject(fn ($row) => !empty($row->email_normalized));

        foreach ($rowsWithEmail->groupBy('email_normalized') as $email => $group) {
            $gpmUuids = $group->filter(fn ($row) => strtoupper($row->source_system) === 'GPM')->pluck('gpm_uuid')->filter(fn ($uuid) => !empty($uuid) && strtoupper(trim($uuid)) !== 'NULL')->unique()->values();
            $sourceSystemCount = $group->pluck('source_system')->unique()->count();

            $matchedBy = $sourceSystemCount > 1 ? 'exact_email_cross_system' : 'single_email';

            if ($gpmUuids->count() === 1) {
                $recommendedAction = 'link_existing_gpm';
                $identityStatus = 'ready';
                $needsManualReview = false;
                $reviewNotes = [];
            } elseif ($gpmUuids->count() === 0) {
                $recommendedAction = 'create_new_gpm_uuid';
                $identityStatus = 'ready';
                $needsManualReview = false;
                $reviewNotes = [];
            } else {
                $recommendedAction = 'manual_review_gpm_uuid_conflict';
                $identityStatus = 'blocked';
                $needsManualReview = true;
                $reviewNotes = [
                    'Multiple GPM UUIDs found for the same normalized email. This should not happen if GPM email identity is unique.',
                ];
            }

            $candidateRows[] = $this->buildCandidateRow(
                batchUuid: $batchUuid,
                rows: $group->values(),
                matchedBy: $matchedBy,
                recommendedAction: $recommendedAction,
                identityStatus: $identityStatus,
                needsManualReview: $needsManualReview,
                reviewNotes: $reviewNotes
            );
        }

        foreach ($rowsWithoutEmail as $row) {
            $candidateRows[] = $this->buildCandidateRow(
                batchUuid: $batchUuid,
                rows: collect([$row]),
                matchedBy: 'missing_email',
                recommendedAction: 'cannot_import_to_clerk',
                identityStatus: 'blocked',
                needsManualReview: true,
                reviewNotes: ['Missing email; cannot create Clerk user.']
            );
        }

        DB::transaction(function () use ($candidateRows) {
            foreach ($candidateRows as $candidateRow) {
                DB::table('consortium_identity_candidates')->insert($candidateRow);
            }
        });

        $this->info('Created '.count($candidateRows).' candidate rows.');

        return self::SUCCESS;
    }

    protected function buildCandidateRow(
        string $batchUuid,
        Collection $rows,
        string $matchedBy,
        string $recommendedAction,
        string $identityStatus,
        bool $needsManualReview,
        array $reviewNotes
    ): array {
        $rows = $rows->values();

        $emails = $rows->pluck('email')->filter()->unique()->values();
        $names = $rows->pluck('full_name')->filter()->unique()->values();
        $sourceSystems = $rows->pluck('source_system')->filter()->unique()->values();
        $gpmUuids = $rows->filter(fn ($row) => strtoupper($row->source_system) === 'GPM')->pluck('gpm_uuid')->filter(fn ($uuid) => !empty($uuid) && strtoupper(trim($uuid)) !== 'NULL')->unique()->values();
        $sourceRowIds = $rows->pluck('id')->values()->all();

        // resolvedGpmUuid is the GPM UUID that will be used for the candidate. It can be:
        // - the existing GPM UUID if there's exactly one and the recommended action is to link
        // - a new UUID if the recommended action is to create a new GPM UUID
        $resolvedGpmUuid = null;
        if ($recommendedAction === 'link_existing_gpm' && $gpmUuids->count() === 1) {
            $resolvedGpmUuid = $gpmUuids->first();
        }
        if ($recommendedAction === 'create_new_gpm_uuid') {
            $resolvedGpmUuid = (string) Str::uuid();
        }

        $localIdsBySystem = $rows->groupBy('source_system')->map(fn ($group) => $group->pluck('local_user_id')->values()->all())->toArray();

        $exactEmailCrossSystem = $rows->pluck('email_normalized')->filter()->unique()->count() === 1 && $sourceSystems->count() > 1;
        $sameNameDiffEmailCrossSystem =
            $rows->map(fn ($row) => ($row->last_name_normalized ?? '') . '|' . ($row->first_name_normalized ?? ''))
                ->filter(fn ($v) => $v !== '|')
                ->unique()
                ->count() === 1
            && $rows->pluck('email_normalized')->filter()->unique()->count() > 1
            && $sourceSystems->count() > 1;

        // Prefer GPM data when the same email exists in both GPM and GT.
        $canonicalRow = $rows->first(fn ($row) => strtoupper($row->source_system) === 'GPM') ?? $rows->first();
        $passwordRow = $rows->first(fn ($row) => strtoupper($row->source_system) === 'GPM' && !empty($row->password_digest)) ?? $rows->first(fn ($row) => !empty($row->password_digest));

        $passwordDigest = $passwordRow?->password_digest;
        $passwordHasher = $passwordDigest ? ($passwordRow->password_hasher ?? 'bcrypt') : null;
        $passwordSourceSystem = $passwordRow?->source_system;

        $canonicalEmail = $canonicalRow?->email_normalized ?? $emails->first();
        $canonicalFullName = $canonicalRow?->full_name ?? $names->first();
        [$canonicalFirstName, $canonicalLastName] = $this->splitName($canonicalFullName);

        $flags = [
            'matched_by' => $matchedBy,
            'cross_system' => $sourceSystems->count() > 1,
            'exact_email_cross_system' => $exactEmailCrossSystem,
            'same_name_diff_email_cross_system' => $sameNameDiffEmailCrossSystem,
            'has_existing_gpm_uuid' => $gpmUuids->isNotEmpty(),
            'raw_row_count' => $rows->count(),
            'has_password' => !empty($passwordDigest),
            'password_source_system' => $passwordSourceSystem,
        ];

        return [
            'import_batch_uuid' => $batchUuid,
            'candidate_uuid' => (string) Str::uuid(),
            'gpm_uuid' => $gpmUuids->count() === 1 ? $gpmUuids->first() : null,
            'resolved_gpm_uuid' => $resolvedGpmUuid,

            'canonical_email' => $canonicalEmail,
            'canonical_full_name' => $canonicalFullName,
            'canonical_first_name' => $canonicalFirstName,
            'canonical_last_name' => $canonicalLastName,

            'password_digest' => $passwordDigest,
            'password_hasher' => $passwordHasher,
            'password_source_system' => $passwordSourceSystem,

            'matched_by' => $matchedBy,
            'identity_status' => $identityStatus,
            'recommended_action' => $recommendedAction,

            'exact_email_cross_system' => $exactEmailCrossSystem,
            'same_name_diff_email_cross_system' => $sameNameDiffEmailCrossSystem,
            'has_existing_gpm_uuid' => $gpmUuids->isNotEmpty(),
            'needs_manual_review' => $needsManualReview,

            'row_count' => $rows->count(),
            'source_system_count' => $sourceSystems->count(),

            'source_systems' => json_encode($sourceSystems->all()),
            'source_row_ids' => json_encode($sourceRowIds),
            'local_ids_by_system' => json_encode($localIdsBySystem),
            'emails' => json_encode($emails->all()),
            'names' => json_encode($names->all()),
            'flags' => json_encode($flags),

            'review_notes' => empty($reviewNotes) ? null : implode(' ', $reviewNotes),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = trim(mb_strtolower($email));
        return $email === '' ? null : $email;
    }

    protected function normalizeNamePart(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        $name = preg_replace('/[^a-z0-9\s]/u', '', $name);
        $name = trim($name);

        return $name === '' ? null : $name;
    }

    protected function splitName(?string $fullName): array
    {
        if (!$fullName) {
            return [null, null];
        }

        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) === 0) {
            return [null, null];
        }

        if (count($parts) === 1) {
            return [$parts[0], null];
        }

        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }
}