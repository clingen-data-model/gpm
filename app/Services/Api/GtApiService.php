<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GtApiService
{
    protected ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    public function mois(): array
    {
        $response = $this->client->get('/mois');
        return $response->json();
    }

    public function searchDiseases(string $query): array
    {
        $response = $this->client->post('/diseases/search', ['query_string' => $query, 'limit' => 10]);
        return $response->json();
    }

    public function getDiseasesByMondoIds(array $mondoId): array
    {
        $response = $this->client->post('/diseases/mondos', ['mondo_ids' => $mondoId]);
        return $response->json();
    }

    public function getDiseaseByOntologyId(string $ontologyId): array
    {
        $response = $this->client->post('/diseases/ontology', ['ontology_id' => $ontologyId]);
        return $response->json();
    }


    public function searchGenes(string $query): array // SEARCH CURATED GENES
    {
        $response = $this->client->post('/genes/search', ['query' => $query, 'limit' => 10]);
        return $response->json();
    }

    public function genesAvailability(string $query): array // SEARCH AVAILABLE GENES ON GT by GENE SYMBOL
    {
        $response = $this->client->post('/genes/availability', ['where' => [ "gene_symbol" => $query ]]);
        return $response->json();
    }

    public function getGeneSymbolById(int $hgncId, bool $forceRefresh = false): array
    {
        $key = "gt:genes:byid:{$hgncId}";
        if ($forceRefresh) { Cache::forget($key); }
        if (Cache::has($key)) { return Cache::get($key); }
        
        try {
            $response = $this->client->post('/genes/byid', ['hgnc_id' => $hgncId]);

            if ($response->status() === 404) {
                Cache::put("{$key}:miss", true, now()->addMinutes(10));
                return null;
            }
            $response->throw();
            $data = $response->json();

            Cache::put($key, $data, now()->addDay(3));

            return $data ?: null;
        } catch (Throwable $e) {
            if (Cache::has($key)) { return Cache::get($key); }
            return null;
        }
    }

    public function getGeneSymbolBySymbol(string $symbol): array
    {
        $response = $this->client->post('/genes/bysymbol', ['gene_symbol' => $symbol]);
        return $response->json();
    }

    public function lookupGenesBulk(string $genes): array
    {
        $response = $this->client->post('/bulk-lookup', ['gene_symbol' => $genes, 'resource' => 'simple', 'perPage' => 1500]);
        return $response->json(); 
    }

    public function getCurationByID(string $uuid): array
    {
        $response = $this->client->post('/bulk-lookup', ['uuid' => $uuid, 'resource' => 'simple', 'perPage' => 1500]);
        return $response->json();
    }

    public function searchCurations(string $query): array
    {
        $response = $this->client->post('/bulk-lookup', ['filter' => $query, 'resource' => 'simple']);
        return $response->json();
    }

    public function approvalBulkUpload(array $payload, int $chunkSize = 50): array
    {
        $affId  = $payload['affiliation_id'];
        $rows   = $payload['rows'] ?? [];
        $chunks = array_chunk($rows, $chunkSize);

        $results = [];
        foreach ($chunks as $i => $chunk) {
            $chunkPayload          = $payload;
            $chunkPayload['rows']  = $chunk;

            $idem = $this->makeRowsIdemKey($affId, $chunk, $i);

            $res = $this->client->post('/genes/bulkupload', $chunkPayload, [
                'timeout'         => 600,
                'connect_timeout' => 30,
                'retry'           => 0,
                'headers'         => ['Idempotency-Key' => $idem],
            ])->json();

            $results[] = $res;
        }

        return ['chunks' => count($chunks), 'results' => $results];
    }

    private function makeRowsIdemKey(int $affID, array $rows, int $index): string
    {
        $genes = array_map( fn ($r) => strtoupper(trim((string) Arr::get((array)$r, 'gene_symbol', ''))), $rows);
        sort($genes);
        return 'gt-bulkupload:' . $affID . ':' . $index . ':' . sha1(json_encode($genes));
    }

    public function syncPanelMembers(int $affiliationId, array $members, string $mode = 'add'): array
    {
        $resp = $this->client->post('/affiliations/members/sync', [
                            'affiliation_id' => $affiliationId,
                            'members'        => $members,
                            'mode'           => $mode,
                        ]);
        return $resp->json();
    }

}
