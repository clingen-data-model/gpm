<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\Api\GtApiService;

class GtApiServiceIntegrationTest extends TestCase
{
    public GtApiService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = app(GtApiService::class);
    }

    public function test_search_genes_api()
    {
        $result = $this->service->searchGenes('BRCA1');
        $this->assertArrayStructure([
                    '*' => [
                        "hgnc_id",
                        "gene_symbol",
                        "omim_id",
                        "ncbi_gene_id",
                        "hgnc_name",
                        "hgnc_status",
                        "previous_symbols",
                        "alias_symbols",
                        "date_approved",
                        "date_modified",
                        "date_symbol_changed",
                        "date_name_changed",
                        "created_at",
                        "updated_at",
                        "deleted_at",
                    ]
                ], $result);
        // $this->assertArrayHasKey('data', $result);
    }

    public function test_get_gene_symbol_by_id_api()
    {
        $result = $this->service->getGeneSymbolById(21); // Example HGNC ID
        $this->assertIsArray($result);
        $this->assertArrayStructure([
                        "hgnc_id",
                        "gene_symbol"                    
                ], $result);
    }

    public function test_get_gene_symbol_by_symbol_api()
    {
        $result = $this->service->getGeneSymbolBySymbol('ABCA1'); // Example HGNC ID
        $this->assertIsArray($result);
        $this->assertArrayStructure([
                        "hgnc_id",
                        "gene_symbol"                    
                ], $result);
    }

    public function test_get_genes_curation_api()
    {
        $result = $this->service->lookupGenesBulk('ABCA, ABCA10, SSX1'); // Example HGNC ID
        $this->assertIsArray($result);
        $this->assertArrayStructure([
                            '*' => [
                                "id",
                                "gene_symbol",
                                "disease",
                                "mondo_id",
                                "expert_panel",
                                "moi",
                                "classification",
                                "curation_type",
                                "current_status",
                                "current_status_date",
                                "phenotype",
                            ]               
                        ], $result);
    }

    public function test_get_disease_by_mondo_id_api()
    {
        $result = $this->service->getDiseaseByMondoId('MONDO:0005148');
        $this->assertIsArray($result);
        $this->assertArrayStructure([
                                'id', 
                                'mondo_id', 
                                'doid_id', 
                                'name', 
                                'is_obsolete', 
                                'replaced_by', 
                                'created_at', 
                                'updated_at'
                        ], $result);
    }

    public function test_get_diseases_by_mondo_ids_api()
    {
        $result = $this->service->getDiseasesByMondoIds(['MONDO:0005148', 'MONDO:0019391']);
        $this->assertIsArray($result);
        $this->assertArrayStructure([
                        "*" => [
                                'id',
                                'mondo_id',
                                'doid_id',
                                'name',
                                'is_obsolete',
                                'replaced_by'
                            ]
                ], $result);
    }

    public function test_get_diseases_by_ontology_ids_api()
    {
        $result = $this->service->getDiseaseByOntologyId('DOID:0050136');
        $this->assertIsArray($result);
        $this->assertArrayStructure([
                        "ontology",
                        "ontology_id",
                        "name"
                ], $result);
    }


    public function assertArrayStructure(array $structure, array $data)
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                // When the key is '*', check every item in the array
                if ($key === '*') {
                    $this->assertIsArray($data);
                    foreach ($data as $item) {
                        $this->assertArrayStructure($value, $item);
                    }
                } else {
                    $this->assertArrayHasKey($key, $data);
                    $this->assertArrayStructure($value, $data[$key]);
                }
            } else {
                $this->assertArrayHasKey($value, $data);
            }
        }
    }

}
