<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Modules\ExpertPanel\Models\Gene;
use App\DataTransferObjects\GtGeneDto;
use App\DataTransferObjects\GtDiseaseDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;

class GeneDtoApiIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // clear cache so API calls happen
    }

    /** @test */
    public function it_fetches_gene_and_disease_data_from_api()
    {
        // Use real IDs that exist in your API
        $gene = Gene::factory()->create([
            'hgnc_id' => 218,
            'mondo_id' => 'MONDO:0016054',
            'disease_name' => 'obsolete cerebral malformation',
        ]);

        $geneDto = $gene->gene();
        $diseaseDto = $gene->disease();
        // dd($geneDto, $diseaseDto); // Debugging line to inspect the DTOs

        // Assert Gene DTO
        $this->assertInstanceOf(GtGeneDto::class, $geneDto);
        $this->assertEquals(218, $geneDto->hgnc_id);
        $this->assertNotEmpty($geneDto->gene_symbol);

        // Assert Disease DTO
        $this->assertInstanceOf(GtDiseaseDto::class, $diseaseDto);
        $this->assertEquals('MONDO:0016054', $diseaseDto->mondo_id);
        $this->assertNotEmpty($diseaseDto->name);
    }
}
