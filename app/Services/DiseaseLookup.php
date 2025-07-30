<?php
namespace App\Services;

use App\Services\DiseaseLookupInterface;
use Exception;
use App\Services\Api\GtApiService;

class DiseaseLookup implements DiseaseLookupInterface
{
    const SUPPORTED_ONTOLOGIES = ['mondo', 'doid'];

    protected GtApiService $gtApiService;

    public function __construct(GtApiService $gtApiService)
    {
        $this->gtApiService = $gtApiService;
    }

    public function findNameByOntologyId(string $ontologyId): string
    {
        $ontology = strtolower(explode(':', $ontologyId)[0]);
        if (!in_array($ontology, static::SUPPORTED_ONTOLOGIES)) {
            throw new Exception('Ontology '.$ontology.' is not supported');
        }

        return $this->gtApi->getDiseaseByOntologyId($ontologyId);
    }
}