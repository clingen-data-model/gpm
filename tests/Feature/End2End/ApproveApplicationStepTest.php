<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApproveApplicationStep1Test extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->application = Application::factory()->create(['ep_type_id'=>config('expert_panels.types.vcep.id')]);
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function can_approve_step()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
        ];

        $this->actingAs($this->user)
            ->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200)
            ->assertJson($this->application->fresh()->toArray());

        $this->assertDatabaseHas('applications', [
            'uuid' => $this->application->uuid,
            'current_step' => (string)2,
            'approval_dates' => json_encode([
                'step 1' => $approvalData['date_approved'],
            ]),
        ]);
    }
    
    
}
