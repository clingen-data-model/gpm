<?php

namespace Tests\Feature\End2End\Applications;

use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApproveStepTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->application = Application::factory()->vcep()->create();
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

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
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

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$badUuid.'/current-step/approve', $approvalData)
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

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(422)
            ->assertJsonFragment(['date_approved' => ['The date approved is not a valid date.']]);
    }
    
    
}
