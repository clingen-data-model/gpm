<?php

namespace Tests\Feature\End2End\Groups;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Tasks\Actions\TaskCreate;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteSustainedCurationReviewTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->expertPanel = ExpertPanel::factory()->vcep()->create([
            'current_step' => 4,
            'step_1_approval_date' => Carbon::now()->addDays(730),
            'step_2_approval_date' => Carbon::now()->addDays(365),
            'step_3_approval_date' => Carbon::now()->addDays(164),
        ]);

        $this->task = (new TaskCreate)->handle($this->expertPanel->group, 'sustained-curation-review');

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_complete_sustained_curation_task()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }
    

    /**
     * @test
     */
    public function privileged_user_can_complete_sustained_curation_review()
    {
        Carbon::setTestNow('2022-01-01');
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->task->id,
                'completed_at' => Carbon::now()
            ]);
    }

    private function makeRequest()
    {
        return $this->json('PUT', '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/sustained-curation-reviews');
    }
}
