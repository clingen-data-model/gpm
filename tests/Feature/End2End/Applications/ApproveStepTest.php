<?php

namespace Tests\Feature\End2End;

use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use Ramsey\Uuid\Uuid;
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
    
    /**
     * @test
     */
    public function returns_404_if_application_not_found()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
        ];

        $badUuid = Uuid::uuid4();

        $this->actingAs($this->user)
            ->json('POST', '/api/applications/'.$badUuid.'/current-step/approve', $approvalData)
            ->assertStatus(404);
    }
    
    /**
     * @test
     */
    public function validates_date_approved()
    {
        $approvalData = [
            'date_approved' => 'Carbon::now()',
        ];

        $this->actingAs($this->user)
            ->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(422)
            ->assertJsonFragment(['date_approved' => ['The date approved is not a valid date.']]);
    }
    
    
}
