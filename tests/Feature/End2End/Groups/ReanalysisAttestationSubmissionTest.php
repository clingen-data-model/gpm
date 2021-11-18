<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReanalysisAttestationSubmissionTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();

        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2021-11-14');
    }
    
    /**
     * @test
     */
    public function unprivileged_user_cannot_submit_reanalysis_attestation()
    {
        $this->submitRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_data()
    {
        $this->user->givePermissionTo('ep-applications-manage');

        $this->submitRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'reanalysis_conflicting' => ['This is required unless you explain differences.'],
            ])
            ->assertJsonFragment([
                'reanalysis_review_lp' => ['This is required unless you explain differences.'],
            ])
            ->assertJsonFragment([
                'reanalysis_review_lb' => ['This is required unless you explain differences.'],
            ])
            ->assertJsonFragment([
                'reanalysis_other' => ['This field is required when no other options are checked.'],
            ]);
    }
    

    /**
     * @test
     */
    public function privileged_user_can_submit_reanalysis_attestation()
    {
        $this->user->givePermissionTo('ep-applications-manage');

        $this->submitRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'reanalysis_conflicting' => 1,
            'reanalysis_review_lp' => 1,
            'reanalysis_review_lb' => 1,
            'reanalysis_other' => 'bob dobs',
            'reanalysis_attestation_date' => Carbon::now()
        ]);
    }

    /**
     * @test
     */
    public function logs_activity()
    {
        $this->user->givePermissionTo('ep-applications-manage');
        
        $this->submitRequest()
            ->assertStatus(200);

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: 'Reanalysis attestation submitted by '.$this->user->name.' on '.Carbon::now()->format('Y-m-d').' at '.Carbon::now()->format('H:i:s').'.',
            activity_type: 'reanalysis-attestation-submitted',
            logName: 'groups'
        );
    }
    

    private function submitRequest($data = null)
    {
        $data = $data ?? [
            'reanalysis_conflicting' => true,
            'reanalysis_review_lp' => true,
            'reanalysis_review_lb' => true,
            'reanalysis_other' => 'bob dobs'
        ];

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/application/reanalysis', $data);
    }
}
