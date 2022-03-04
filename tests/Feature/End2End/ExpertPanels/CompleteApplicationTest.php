<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
    }

    /**
     * @test
     */
    public function gcep_application_completed_when_step1_approved()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
        $dateApproved = Carbon::parse('2021-09-16');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$expertPanel->uuid.'/current-step/approve', ['date_approved' => $dateApproved])
            ->assertStatus(200)
            ->assertJsonFragment([
                'date_completed' => $dateApproved->toJson(),
            ])
            ->assertJsonFragment([
                'current_step' => 1
            ]);
    }
    
    /**
     * @test
     */
    public function vcep_application_completed_when_step4_approved()
    {
        $expertPanel = ExpertPanel::factory()->vcep()->create();
        app()->make(StepApprove::class)->handle($expertPanel, Carbon::parse('2021-01-02'));
        // $expertPanel->approveCurrentStep(Carbon::parse('2021-01-02'));
        $expertPanel = $expertPanel->fresh();
        $this->assertEquals(2, $expertPanel->current_step);
        $this->assertNull($expertPanel->date_completed);

        // $expertPanel->approveCurrentStep(Carbon::parse('2021-01-03'));
        app()->make(StepApprove::class)->handle($expertPanel, Carbon::parse('2021-01-03'));
        $expertPanel = $expertPanel->fresh();
        $this->assertEquals(3, $expertPanel->current_step);
        $this->assertNull($expertPanel->date_completed);
        
        // $expertPanel->approveCurrentStep(Carbon::parse('2021-01-04'));
        app()->make(StepApprove::class)->handle($expertPanel, Carbon::parse('2021-01-04'));
        $expertPanel = $expertPanel->fresh();
        $this->assertEquals(4, $expertPanel->current_step);
        $this->assertNull($expertPanel->date_completed);

        $dateApproved = Carbon::parse('2021-09-16');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$expertPanel->uuid.'/current-step/approve', ['date_approved' => $dateApproved])
            ->assertStatus(200)
            ->assertJsonFragment([
                'date_completed' => $dateApproved->toJson()
            ]);
    }
}
