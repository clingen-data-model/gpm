<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\AttestationReanalysisStore;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class AttestationReanalysisTest extends TestCase
{
    use FastRefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();

        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2021-11-14');
    }
    
    /**
     * @test
     */
    public function unprivileged_user_cannot_submit_reanalysis_attestation()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        $this->submitRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_data()
    {
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
        $this->submitRequest()
            ->assertStatus(200);

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: 'Reanalysis attestation submitted by '.$this->user->name.' on '.Carbon::now()->format('Y-m-d').' at '.Carbon::now()->format('H:i:s').'.',
            activity_type: 'reanalysis-attestation-submitted',
            logName: 'groups'
        );
    }

    /**
     * @test
     */
    public function handler_does_not_set_attestation_date_if_requirements_not_met()
    {
        $data = [
            'reanalysis_conflicting' => false,
            'reanalysis_review_lp' => false,
            'reanalysis_review_lb' => true,
            'reanalysis_other' => null
        ];

        $action = app()->make(AttestationReanalysisStore::class);
        $group = $action->handle($this->expertPanel->group, ...$data);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'reanalysis_conflicting' => false,
            'reanalysis_review_lp' => false,
            'reanalysis_review_lb' => true,
            'reanalysis_other' => null,
            'reanalysis_attestation_date' => null,
        ]);
    }
    
    

    private function submitRequest($data = null)
    {
        $data = $data ?? [
            'reanalysis_conflicting' => true,
            'reanalysis_review_lp' => true,
            'reanalysis_review_lb' => true,
            'reanalysis_other' => 'bob dobs'
        ];

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/attestations/reanalysis', $data);
    }
}
