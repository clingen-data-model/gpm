<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportConsortiumIdentityCsv extends Command
{
    protected $signature = 'consortium-identities:import-csv
        {path : Full path to the CSV file}
        {--batch= : Optional import_batch_uuid}
        {--truncate : Truncate consortium_identity_import_rows before import}';

    protected $description = 'Import a consortium identity CSV into consortium_identity_import_rows';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $batchUuid = $this->option('batch') ?: (string) Str::uuid();

        if ($this->option('truncate')) {
            DB::table('consortium_identity_import_rows')->truncate();
            $this->warn('Truncated consortium_identity_import_rows.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Unable to open file: {$path}");
            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('CSV appears to be empty.');
            return self::FAILURE;
        }

        $header = array_map(fn ($v) => trim((string) $v), $header);

        $required = ['source_system', 'local_user_id', 'email', 'full_name', 'gpm_uuid'];
        foreach ($required as $field) {
            if (!in_array($field, $header, true)) {
                fclose($handle);
                $this->error("Missing required CSV column: {$field}");
                return self::FAILURE;
            }
        }

        $headerMap = array_flip($header);
        $rows = [];
        $count = 0;
        $skipped = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $sourceSystem = $this->csvValue($data, $headerMap, 'source_system');
            $localUserId = $this->csvValue($data, $headerMap, 'local_user_id');
            $email = $this->nullIfEmpty($this->csvValue($data, $headerMap, 'email'));
            $fullName = $this->nullIfEmpty($this->csvValue($data, $headerMap, 'full_name'));
            $gpmUuid = $this->nullIfEmpty($this->csvValue($data, $headerMap, 'gpm_uuid'));

            if (!$sourceSystem || !$localUserId) {
                $skipped++;
                continue;
            }

            [$firstName, $lastName] = $this->splitName($fullName);
            $password = $this->nullIfEmpty($this->csvValue($data, $headerMap, 'password'));

            $row = [
                'import_batch_uuid' => $batchUuid,
                'source_system' => strtoupper(trim($sourceSystem)),
                'local_user_id' => trim($localUserId),

                'email' => $email,
                'email_normalized' => $this->normalizeEmail($email),

                'full_name' => $fullName,
                'full_name_normalized' => $this->normalizeName($fullName),

                'gpm_uuid' => $gpmUuid,
                'password_digest' => $password,
                'password_hasher' => $password ? 'bcrypt' : null,

                'first_name' => $firstName,
                'last_name' => $lastName,

                'has_email' => !empty($email),
                'has_gpm_uuid' => !empty($gpmUuid),

                'flags' => json_encode([
                    'source_system' => strtoupper(trim($sourceSystem)),
                ]),

                'raw_payload' => json_encode([
                    'source_system' => $sourceSystem,
                    'local_user_id' => $localUserId,
                    'email' => $email,
                    'full_name' => $fullName,
                    'gpm_uuid' => $gpmUuid,
                    'has_password' => !empty($password),
                ]),

                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rows[] = $row;

            if (count($rows) >= 500) {
                DB::table('consortium_identity_import_rows')->insert($rows);
                $count += count($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if (!empty($rows)) {
            DB::table('consortium_identity_import_rows')->insert($rows);
            $count += count($rows);
        }

        $this->info("Imported {$count} rows.");
        $this->info("Skipped {$skipped} rows.");
        $this->info("import_batch_uuid: {$batchUuid}");

        return self::SUCCESS;
    }

    protected function csvValue(array $data, array $headerMap, string $key): ?string
    {
        if (!array_key_exists($key, $headerMap)) {
            return null;
        }

        $index = $headerMap[$key];
        return array_key_exists($index, $data) ? (string) $data[$index] : null;
    }

    protected function nullIfEmpty(?string $value): ?string
    {
        if ($value === null) { return null; }
        $value = trim($value);
        if ($value === '' || strtoupper($value) === 'NULL' || $value === '\\N') {
            return null;
        }
        return $value;
    }

    protected function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        return mb_strtolower(trim($email));
    }

    protected function normalizeName(?string $name): ?string
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