<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\ExpertPanel\Jobs\ApproveStep;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApproveStepTest extends TestCase
{
    use RefreshDatabase;
        
    /**
     * @test
     */
    public function fires_StepApproved_event_when_step_approved_and_logs_activity()
    {
        $this->seed();
        $application = ExpertPanel::factory()->create();

        $dateApproved = Carbon::today();
        ApproveStep::dispatch($application->uuid, $dateApproved);

        // $this->assertDatabaseHas('activity_log', [
        //     'log_name' => 'applications',
        //     'description' => 'Step 1 approved',
        //     'properties->date_approved' => $dateApproved->toISOString(),
        //     'properties->activity_type' => 'step-approved',
        //     'properties->step' => 1
        // ]);
        $this->assertLoggedActivity(
            $application, 
            'Step 1 approved', 
            [
                'date_approved' => $dateApproved->toIsoString(), 
                'step' => 1, 
                'activity_type' => 'step-approved'
            ]
        );
    }

}
