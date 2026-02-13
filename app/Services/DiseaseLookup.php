<?php
namespace App\Services;

use App\Services\DiseaseLookupInterface;
use Exception;
use App\Services\Api\GtApiService;

class DiseaseLookup implements DiseaseLookupInterface
{
    const SUPPORTED_ONTOLOGIES = ['mondo', 'doid'];

    protected GtApiService $gtApi;

    public function __construct(GtApiService $gtApi)
    {
        $this->gtApi = $gtApi;
    }

    public function findNameByOntologyId(string $ontologyId): string
    {
        $ontology = strtolower(explode(':', $ontologyId)[0]);
        if (!in_array($ontology, static::SUPPORTED_ONTOLOGIES)) {
            throw new Exception('Ontology '.$ontology.' is not supported');
        }

        $result = $this->gtApi->getDiseaseByOntologyId($ontologyId);
        return $result['name'] ?? '';
    }
}