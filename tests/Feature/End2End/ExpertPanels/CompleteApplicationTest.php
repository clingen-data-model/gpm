<?php

namespace Tests\Feature\End2End\ExpertPanels;

use App\Models\Activity;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class CompleteApplicationTest extends TestCase
{
    use FastRefreshDatabase;

    private $user, $expertPanel;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
    }

    /**
     * @test
     */
    public function gcep_application_completed_when_step1_approved()
    {
        $dateApproved = Carbon::parse('2021-09-16');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->makeRequest(['date_approved' => $dateApproved]);
        $response->assertStatus(200);
        $response->assertJsonFragment([
                'date_completed' => $dateApproved->toJson(),
        ]);
        $response->assertJsonFragment([
                'current_step' => 1
            ]);
    }

    /**
     * @test
     */
    public function vcep_application_completed_when_step4_approved()
    {
        $this->expertPanel->group->update(['group_type_id' => 3]);
        $this->expertPanel->update(['expert_panel_type_id' => 2]);

        app()->make(StepApprove::class)->handle($this->expertPanel, Carbon::parse('2021-01-02'));
        $this->expertPanel->refresh();
        $this->assertEquals(2, $this->expertPanel->fresh()->current_step);
        $this->assertNull($this->expertPanel->date_completed);

        app()->make(StepApprove::class)->handle($this->expertPanel, Carbon::parse('2021-01-03'));
        $this->expertPanel->refresh();
        $this->assertEquals(3, $this->expertPanel->current_step);
        $this->assertNull($this->expertPanel->date_completed);

        app()->make(StepApprove::class)->handle($this->expertPanel, Carbon::parse('2021-01-04'));
        $this->expertPanel->refresh();
        $this->assertEquals(4, $this->expertPanel->current_step);
        $this->assertNull($this->expertPanel->date_completed);

        $dateApproved = Carbon::parse('2021-09-16');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->makeRequest(['date_approved' => $dateApproved])
            ->assertStatus(200)
            ->assertJsonFragment([
                'date_completed' => $dateApproved->toJson()
            ]);
    }

    private function makeRequest($data = null)
    {
        $data = $data ?? null;

        return $this->json('POST', '/api/applications/'.$this->expertPanel->group->uuid.'/current-step/approve', $data);
    }

}
