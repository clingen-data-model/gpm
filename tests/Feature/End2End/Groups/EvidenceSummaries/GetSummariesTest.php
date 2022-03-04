<?php

namespace Tests\Feature\End2End\Groups\EvidenceSummaries;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\ExpertPanel\Models\Gene;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Models\EvidenceSummary;

class GetSummariesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    public function setup():void
    {
        parent::setup();
        $this->setUpForGroupTest();
        $this->genes = $this->seedGenes();

        $this->user = User::factory()->create();
        $this->vcep = ExpertPanel::factory()->vcep()->create();
        $this->vcepGenes = $this->vcep->genes()->saveMany(
            $this->genes->map(function ($hgnc) {
                return new Gene(['hgnc_id' => $hgnc->hgnc_id, 'gene_symbol' => $hgnc->gene_symbol, 'mondo_id' => 'MONDO:1234567']);
            })
        );

        $this->summary = EvidenceSummary::factory()->create(['expert_panel_id' => $this->vcep->id, 'gene_id' => $this->vcepGenes->first()->id]);

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function retrieves_evidence_summaries_for_expert_panel()
    {
        $this->json('GET', '/api/groups/'.$this->vcep->group->uuid.'/expert-panel/evidence-summaries')
            ->assertStatus(200)
            ->assertJson([
                'data' => [[
                    'id' => $this->summary->id,
                    'expert_panel_id' => $this->vcep->id,
                    'gene_id' => $this->vcepGenes->first()->id,
                    'summary' => $this->summary->summary,
                ]]
            ]);
    }
}
