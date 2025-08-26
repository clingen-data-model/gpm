<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Modules\Group\Models\Group;

class SyncGroupParentsFromAffils extends Command
{
    protected $signature = 'groups:sync-cdwg-parents
        {--dry : Preview changes and creations without writing}
        {--debug : Print HTTP status and body snippets}
        {--create-missing : Create CDWGs in Affils for unmatched local groups}';

    protected $description = 'Sync groups.parent_id from Affils CDWG list by name; optionally create missing CDWGs and update parent_id.';

    public function handle(): int
    {
        // Config/env with sensible defaults
        $base    = rtrim(config('services.affils.base_url'), '/');
        $listUrl = $base . config('services.affils.endpoints.cdwg_list');     // https://.../api/cdwg_list/
        $createUrl = $base . config('services.affils.endpoints.cdwg_create'); // https://.../api/cdwg/create/
        $apiKey  = config('services.affils.api_key');

        $dry            = (bool) $this->option('dry');
        $debug          = (bool) $this->option('debug');
        $createMissing  = (bool) $this->option('create-missing');

        $http = Http::timeout(30)
            ->acceptJson()
            ->withHeaders(['x-api-key' => $apiKey]);

        // 1) Fetch current CDWG list
        $resp = $http->get($listUrl);
        if ($debug) $this->dumpHttp('GET', $listUrl, $resp);

        if ($resp->failed()) {
            $this->error("GET list failed: HTTP {$resp->status()}");
            return self::FAILURE;
        }

        $json = json_decode($resp->body(), true);
        if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->error('List response is not valid JSON.');
            return self::FAILURE;
        }

        // Accept array or common wrappers
        $list = null;
        if (is_array($json)) {
            if (array_is_list($json)) {
                $list = $json;
            } else {
                foreach (['data', 'results', 'items', 'cdwgs'] as $k) {
                    if (isset($json[$k]) && is_array($json[$k])) {
                        $list = $json[$k];
                        break;
                    }
                }
            }
        }
        if (!is_array($list)) {
            $this->error('Unexpected list format (expected array of {id,name}).');
            return self::FAILURE;
        }

        // name -> id map (case-insensitive)
        $apiMap = [];
        foreach ($list as $row) {
            if (!is_array($row) || !isset($row['id'], $row['name'])) {
                $this->warn('Skipping malformed row: ' . json_encode($row));
                continue;
            }
            $name = trim((string) $row['name']);
            if ($name === '') continue;
            $lower = mb_strtolower($name);
            if (isset($apiMap[$lower])) {
                $this->warn("Duplicate name in API list: '{$name}' (overwriting {$apiMap[$lower]} with {$row['id']})");
            }
            $apiMap[$lower] = (int) $row['id'];
        }

        // 2) Load local candidates: type=2, not deleted, parent is NULL
        $candidates = Group::query()
            ->where('group_type_id', 2)
            ->whereNull('parent_id')
            ->whereNull('deleted_at') // keep explicit SQL alignment
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No local candidate groups (type=2, parent_id IS NULL, not deleted).');
            return self::SUCCESS;
        }

        $updated = 0;
        $unchanged = 0;
        $created = 0;
        $skipped = 0;
        $unmatchedForCreation = [];

        // 3) Match or create
        foreach ($candidates as $g) {
            $key = mb_strtolower(trim((string) $g->name));
            $apiId = $apiMap[$key] ?? null;

            if ($apiId !== null) {
                // Already exists in Affils -> update parent_id if needed
                if ((int) $g->parent_id === (int) $apiId) { $unchanged++; continue; }

                if ($dry) {
                    $this->line("[DRY] UPDATE group {$g->id} '{$g->name}': parent_id NULL -> {$apiId}");
                } else {
                    $g->parent_id = $apiId;
                    $g->save();
                }
                $updated++;
                continue;
            }

            // Not in Affils yet
            if (!$createMissing) {
                $unmatchedForCreation[] = $g->name;
                continue;
            }

            if ($dry) {
                $this->line("[DRY] CREATE CDWG '{$g->name}' in Affils, then set group {$g->id}.parent_id to <new id>");
                $created++;
                continue;
            }

            // Create in Affils
            $payload = ['name' => $g->name];
            $post = $http->post($createUrl, $payload);
            if ($debug) $this->dumpHttp('POST', $createUrl, $post, $payload);

            if ($post->status() === 201) {
                $createdJson = $post->json();
                $newId = (int) ($createdJson['id'] ?? 0);
                if ($newId > 0) {
                    $g->parent_id = $newId;
                    $g->save();
                    $apiMap[$key] = $newId; // prevent dup attempts within the same run
                    $created++;
                } else {
                    $this->warn("Created but response missing 'id' for '{$g->name}'. Body: " . mb_substr($post->body(), 0, 400));
                    $skipped++;
                }
            } elseif ($post->status() === 400) {
                $this->warn("Validation failed creating '{$g->name}': " . mb_substr($post->body(), 0, 400));
                $skipped++;
            } else {
                $this->warn("Create failed for '{$g->name}': HTTP {$post->status()} " . mb_substr($post->body(), 0, 400));
                $skipped++;
            }
        }

        $this->info("Done. Updated: {$updated}, Unchanged: {$unchanged}, Created: {$created}, Skipped: {$skipped}.");

        if (!$createMissing && !empty($unmatchedForCreation)) {
            $this->warn('Unmatched local groups (not created; re-run with --create-missing):');
            foreach ($unmatchedForCreation as $n) $this->line(' - ' . $n);
        }

        return self::SUCCESS;
    }

    private function dumpHttp(string $method, string $url, \Illuminate\Http\Client\Response $resp, array $payload = []): void
    {
        $status = $resp->status();
        $this->line("{$method} {$url} :: HTTP {$status}");
        if (!empty($payload)) $this->line('Payload: ' . json_encode($payload));
        $this->line('Body (first 400 chars): ' . mb_substr($resp->body() ?? '', 0, 400));
    }
}
