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

        try {
            $response = $this->gtApi->getDiseaseByOntologyId($ontologyId);

            if (!($response['success'] ?? false) || empty($response['data']['name'])) {
                throw new \Exception("We couldn't find a disease with {$ontology} ID {$ontologyId} in our records.");
            }

            return $response['data']['name'];
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve disease data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
