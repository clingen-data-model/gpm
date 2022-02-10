<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StepApproveTest extends TestCase
{
    use RefreshDatabase;
        
    /**
     * @test
     */
    public function fires_StepApproved_event_when_step_approved_and_logs_activity()
    {
        $this->seed();
        $expertPanel = ExpertPanel::factory()->create(['current_step' => 1]);

        $dateApproved = Carbon::today();
        app()->make(StepApprove::class)->handle($expertPanel, $dateApproved);

        $this->assertLoggedActivity(
            $expertPanel->group,
            'Step 1 approved',
            [
                'date_approved' => $dateApproved->toIsoString(),
                'step' => 1,
                'activity_type' => 'step-approved'
            ]
        );
    }
}
