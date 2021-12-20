<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\SeedsHgncGenesAndDiseases;

/**
 * @group applications
 * @group expert-panels
 * @group gene-list
 * @group genes
 */
class GetGenesForGroupTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->seedGenes([
            ['hgnc_id' => 12345, 'gene_symbol' => 'ABC1'],
            ['hgnc_id' => 987654, 'gene_symbol' => 'DEF1']
        ]);
        $this->seedDiseases([
            ['mondo_id' => 'MONDO:1234567', 'name' => 'beans'],
            ['mondo_id' => 'MONDO:9876543', 'name' => 'monkeys']
        ]);

        $this->user = User::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/genes';
        GenesAdd::run($this->expertPanel->group, [
            ['hgnc_id' => 12345, 'mondo_id' => 'MONDO:1234567'],
            ['hgnc_id' => 987654, 'mondo_id' => 'MONDO:9876543'],
        ]);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function returns_404_if_group_is_not_an_expert_panel()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.wg.id')]);
        $this->json('GET', $this->url)
            ->assertStatus(404);
    }
    

    /**
     * @test
     */
    public function gets_all_genes_for_a_group()
    {
        $response = $this->json('GET', $this->url);
        $response->assertStatus(200);

        $this->assertEquals(2, $response->original->count());
        $response->assertJsonFragment(['hgnc_id' => 12345]);
        $response->assertJsonFragment(['mondo_id' => 'MONDO:1234567']);
        $response->assertJsonFragment(['hgnc_id' => 987654, 'mondo_id' => 'MONDO:9876543']);
    }
}
