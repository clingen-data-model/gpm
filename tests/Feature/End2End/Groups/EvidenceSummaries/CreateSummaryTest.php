<?php

namespace Tests\Feature\End2End\Groups\EvidenceSummaries;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\Gene;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateSummaryTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->genes = $this->seedGenes();

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);

        $this->vcep = ExpertPanel::factory()->vcep()->create();
        $this->vcepGenes = $this->vcep->genes()->saveMany(
            $this->genes->map(function ($hgnc) {
                return new Gene(['hgnc_id' => $hgnc->hgnc_id, 'gene_symbol' => $hgnc->gene_symbol, 'mondo_id' => 'MONDO:1234567']);
            })
        );
        Sanctum::actingAs($this->user);

        $this->url = '/api/groups/'.$this->vcep->group->uuid.'/expert-panel/evidence-summaries';
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_create_new_evidenceSummary()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_required_data()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'gene_id' => ['This field is required.'],
                'summary' => ['This field is required.'],
                'variant' => ['This field is required.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_data_format()
    {
        $this->makeRequest([
            'gene_id' => 1823198319,
            'vci_url' => 'blah blah blah',
            'variant' => 'thisi s a bunch of charactser that will ultimately be over 255 characters.thisi s a bunch of charactser that will ultimately be over 255 characters.thisi s a bunch of charactser that will ultimately be over 255 characters.thisi s a bunch of charactser that'
        ])->assertStatus(422)
            ->assertJsonFragment([
                'gene_id' => ['The gene was not found in your scope.'],
                'vci_url' => ['The vci url format is invalid.'],
                'variant' => ['The variant may not be greater than 255 characters.']
            ]);
    }
    

    /**
     * @test
     */
    public function validates_group_is_vcep()
    {
        $this->vcep->update(['expert_panel_type_id' => config('expert_panels.types.gcep.id')]);
        $this->makeRequest()
            ->assertStatus(422)
            ->assertJsonFragment([
                'group' => ['You can not add an evidence summary to this group. Only VCEPs have evidence summaries.']
            ]);
    }

    /**
     * @test
     */
    public function privileged_user_can_add_an_evidence_summary()
    {
        $expectedData = [
            'expert_panel_id' => $this->vcep->id,
            'summary' => 'blah blah blah',
            'gene_id' => $this->vcepGenes->first()->id,
            'variant' => 'Some variant',
            'vci_url' => 'https://clinicalgenome.org'
        ];

        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'data' => $expectedData
            ]);

        $this->assertDatabaseHas('evidence_summaries', $expectedData);
    }

    /**
     * @test
     */
    public function logs_activity()
    {
        $this->makeRequest();

        $this->assertDatabaseHas('activity_log', [
            'activity_type' => 'evidence-summary-added',
            'subject_type' => Group::class,
            'subject_id' => $this->vcep->group_id,
            'properties->evidence_summary->summary' => 'blah blah blah',
            'properties->evidence_summary->id' => 1,
        ]);
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'gene_id' => $this->vcepGenes->first()->id,
            'summary' => 'blah blah blah',
            'variant' => 'Some variant',
            'vci_url' => 'https://clinicalgenome.org'
        ];
        return $this->json('POST', $this->url, $data);
    }
}
