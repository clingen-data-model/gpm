<?php

namespace Tests\Feature\End2End\Applications;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use App\Modules\Application\Jobs\ApproveStep;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function gcep_application_completed_when_step1_approved()
    {
        $application = Application::factory()->gcep()->create();
        $dateApproved = Carbon::parse('2021-09-16');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('POST', '/api/applications/'.$application->uuid.'/current-step/approve', ['date_approved' => $dateApproved])
            ->assertStatus(200)
            ->assertJsonFragment([
                'date_completed' => $dateApproved->toJson(),
                'current_step' => 1
            ]);
    }
    
    /**
     * @test
     */
    public function vcep_application_completed_when_step4_approved()
    {
        $application = Application::factory()->vcep()->create();
        ApproveStep::dispatch($application->uuid, Carbon::parse('2021-01-02'));
        // $application->approveCurrentStep(Carbon::parse('2021-01-02'));
        $application = $application->fresh();
        $this->assertEquals(2, $application->current_step);
        $this->assertNull($application->date_completed);

        // $application->approveCurrentStep(Carbon::parse('2021-01-03'));
        ApproveStep::dispatch($application->uuid, Carbon::parse('2021-01-03'));
        $application = $application->fresh();
        $this->assertEquals(3, $application->current_step);
        $this->assertNull($application->date_completed);
        
        // $application->approveCurrentStep(Carbon::parse('2021-01-04'));
        ApproveStep::dispatch($application->uuid, Carbon::parse('2021-01-04'));
        $application = $application->fresh();
        $this->assertEquals(4, $application->current_step);
        $this->assertNull($application->date_completed);

        $dateApproved = Carbon::parse('2021-09-16');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('POST', '/api/applications/'.$application->uuid.'/current-step/approve', ['date_approved' => $dateApproved])
            ->assertStatus(200)
            ->assertJsonFragment([
                'date_completed' => $dateApproved->toJson()
            ]);
    }
    


    
}
