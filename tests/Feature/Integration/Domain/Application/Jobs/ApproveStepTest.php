<?php

namespace Tests\Feature\Integration\Domain\Application\Jobs;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Domain\Application\Jobs\ApproveStep;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
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
        
        $this->assertLoggedActivity($application, 'Step 1 approved', ['date_approved' => $dateApproved]);
    }

}
