<?php

namespace Tests\Feature\End2End\Groups\EvidenceSummaries;

use App\Modules\ExpertPanel\Models\EvidenceSummary;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\Gene;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateSummaryTest extends TestCase
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
        $this->evidenceSummary = EvidenceSummary::factory()->create([
            'expert_panel_id' => $this->vcep->id
        ]);
        Sanctum::actingAs($this->user);

        $this->url = '/api/groups/'.$this->vcep->group->uuid.'/expert-panel/evidence-summaries/'.$this->evidenceSummary->id;
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_new_evidenceSummary()
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
    public function privileged_user_can_update_an_evidence_summary()
    {
        $expectedData = [
            'expert_panel_id' => $this->vcep->id,
            'summary' => 'Yackety Schmackety',
            'gene_id' => $this->evidenceSummary->gene_id,
            'variant' => 'Some variant',
            'vci_url' => 'https://clinicalgenome.org'
        ];

        $this->makeRequest($expectedData)
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
        $expectedData = [
            'expert_panel_id' => $this->vcep->id,
            'summary' => 'Yackety Schmackety',
            'gene_id' => $this->evidenceSummary->gene_id,
            'variant' => 'Some variant',
            'vci_url' => 'https://clinicalgenome.org'
        ];

        $this->makeRequest($expectedData);

        $this->assertDatabaseHas('activity_log', [
            'activity_type' => 'evidence-summary-updated',
            'subject_type' => Group::class,
            'subject_id' => $this->vcep->group_id,
            'properties->evidence_summary->summary' => 'Yackety Schmackety',
            'properties->evidence_summary->id' => 1,
        ]);
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'gene_id' => $this->evidenceSummary->gene_id,
            'summary' => 'blah blah blah',
            'variant' => 'Some variant',
            'vci_url' => 'https://clinicalgenome.org'
        ];
        return $this->json('PUT', $this->url, $data);
    }
}
