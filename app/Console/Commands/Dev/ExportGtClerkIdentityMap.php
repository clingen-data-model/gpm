<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportGtClerkIdentityMap extends Command
{
    protected $signature = 'consortium-identities:export-gt-clerk-map
        {batch_uuid : Batch UUID/name used for the GPM+GT Clerk migration}';

    protected $description = 'Export GT user ID to Clerk identity mapping from GPM migration data.';

    public function handle(): int
    {
        $importBatchUuid = $this->argument('batch_uuid');
        $outputPath = base_path("gt-clerk-mapping.csv");

        $rowsTable = "consortium_identity_import_rows";
        $candidatesTable = "consortium_identity_candidates";

        $dir = dirname($outputPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $out = fopen($outputPath, 'w');

        if (!$out) {
            $this->error("Could not open output file: {$outputPath}");

            return self::FAILURE;
        }

        fputcsv($out, [
            'gt_user_id',
            'email',
            'clingen_uuid',
            'clerk_user_id',
        ]);

        $written = 0;
        $skipped = 0;

        DB::table('consortium_identity_import_rows as r')
            ->join('consortium_identity_candidates as c', function ($join) {
                $join->on('c.import_batch_uuid', '=', 'r.import_batch_uuid')
                    ->on('c.canonical_email', '=', 'r.email_normalized');
            })
            ->where('r.import_batch_uuid', $importBatchUuid)
            ->where('r.source_system', 'GT')
            ->orderBy('r.local_user_id')
            ->select([
                'r.local_user_id as gt_user_id',
                'r.email_normalized as email',
                'c.resolved_gpm_uuid as clingen_uuid',
                'c.clerk_user_id as clerk_user_id',
                'c.clerk_import_status as clerk_import_status',
            ])
            ->chunk(500, function ($rows) use ($out, &$written, &$skipped) {
                foreach ($rows as $row) {
                    if (
                        empty($row->gt_user_id)
                        || empty($row->email)
                        || empty($row->clingen_uuid)
                        || empty($row->clerk_user_id)
                    ) {
                        $skipped++;
                        continue;
                    }

                    fputcsv($out, [
                        $row->gt_user_id,
                        $row->email,
                        $row->clingen_uuid,
                        $row->clerk_user_id,
                    ]);

                    $written++;
                }
            });

        fclose($out);

        $this->info("Exported GT Clerk mapping: {$outputPath}");
        $this->line("Written: {$written}");
        $this->line("Skipped missing data: {$skipped}");

        return self::SUCCESS;
    }
}