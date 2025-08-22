<?php

namespace App\Services\Api;

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

    
    public function searchGenes(string $query): array
    {
        $response = $this->client->post('/genes/search', ['query' => $query, 'limit' => 10]);
        return $response->json();
    }

    public function getGeneSymbolById(int $hgncId): array
    {
        $response = $this->client->post('/genes/byid', ['hgnc_id' => $hgncId]);
        return $response->json();
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
        $response = $this->client->post('/bulk-lookup?', ['filter' => $query, 'resource' => 'simple']);
        return $response->json();
    }
    
    public function approvalBulkUpload(array $payload): array
    {
        $response = $this->client->post('/genes/bulkupload', $payload);
        return $response->json();
    }
}