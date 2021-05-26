<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\Application\Jobs\ApproveStep;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
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
        $application = Application::factory()->create();

        $dateApproved = Carbon::today();
        ApproveStep::dispatch($application->uuid, $dateApproved);
        
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
