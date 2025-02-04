<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AttestationNhgriTest extends TestCase
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
    public function unprivileged_user_cannot_submit_nhgri_attestation()
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
                'attestation' => ['This is required.']
            ]);

        $this->submitRequest(['attestation' => 0])
            ->assertStatus(422)
            ->assertJsonFragment([
                'attestation' => ['The attestation must be accepted.']
            ]);
    }
    

    /**
     * @test
     */
    public function privileged_user_can_submit_nhgri_attestation()
    {
        $this->submitRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'nhgri_attestation_date' => Carbon::now()
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
            description: 'NHGRI attestation submitted by '.$this->user->name.' on '.Carbon::now()->format('Y-m-d').' at '.Carbon::now()->format('H:i:s').'.',
            activity_type: 'nhgri-attestation-submitted',
            logName: 'groups'
        );
    }
    

    private function submitRequest($data = null)
    {
        $data = $data ?? ['attestation' => true];

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/attestations/nhgri', $data);
    }
}
