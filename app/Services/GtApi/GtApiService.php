<?php

namespace App\Services\GtApi;

class GtApiService
{
    protected GtApiClient $client;

    public function __construct(GtApiClient $client)
    {
        $this->client = $client;
    }

    public function searchGenes(string $query): array
    {
        $response = $this->client->post('/genes/search', ['query' => $query]);
        return $response->json('results') ?? [];
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

    public function searchDiseases(string $query): array
    {
        $response = $this->client->post('/diseases/search', ['query' => $query]);
        return $response->json('results') ?? [];
    }

    public function getDiseaseByMondoId(string $mondoId): array
    {
        $response = $this->client->post('/diseases/mondo', ['mondo_id' => $mondoId]);
        return $response->json();
    }

    public function getDiseaseByOntologyId(string $ontologyId): array
    {
        $response = $this->client->post('/diseases/ontology', ['ontology_id' => $ontologyId]);
        return $response->json();
    }

    public function lookupGenesBulk(string $genes): array
    {
        $response = $this->client->post('/genes/curations', ['gene_symbol' => $genes]);
        return $response->json();
    }
    
    public function approvalBulkUpload(array $payload): array
    {
        $response = $this->client->post('/genes/bulkupload', $payload);
        return $response->json();
    }
}
