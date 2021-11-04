<?php

namespace Tests\Feature\End2End\Groups\EvidenceSummaries;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\Gene;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Models\EvidenceSummary;

class DeleteSummaryTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->genes = $this->seedGenes();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('ep-applications-manage');
        $this->vcep = ExpertPanel::factory()->vcep()->create();
        $this->evidenceSummary = EvidenceSummary::factory()->create([
            'expert_panel_id' => $this->vcep->id
        ]);
        Sanctum::actingAs($this->user);

        $this->url = '/api/groups/'.$this->vcep->group->uuid.'/application/evidence-summaries/'.$this->evidenceSummary->id;
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_delete_evidenceSummary()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        $this->makeRequest()
            ->assertStatus(403);

        $this->assertDatabaseHas('evidence_summaries', [
            'id' => $this->evidenceSummary->id,
            'deleted_at' => null
        ]);
    }

    /**
     * @test
     */
    public function privileged_user_can_delete_an_evidence_summary()
    {
        Carbon::setTestNow('2021-01-01');
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('evidence_summaries', [
            'id' => $this->evidenceSummary->id,
            'deleted_at' => '2021-01-01 00:00:00'
        ]);
    }

    /**
     * @test
     */
    public function logs_activity()
    {
        $this->makeRequest();

        $this->assertDatabaseHas('activity_log', [
            'activity_type' => 'evidence-summary-deleted',
            'subject_type' => Group::class,
            'subject_id' => $this->vcep->group_id,
            'properties->evidence_summary_id' => $this->evidenceSummary->id,
        ]);
    }
    

    private function makeRequest($data = null)
    {
        return $this->json('DELETE', $this->url);
    }
}
