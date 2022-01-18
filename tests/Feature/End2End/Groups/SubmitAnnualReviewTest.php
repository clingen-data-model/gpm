<?php

namespace Tests\Feature\End2End\Groups;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\AnnualReview;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubmitAnnualReviewTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->coordinator = GroupMember::factory()
                                ->create(['group_id' => $this->expertPanel->group->id])
                                ->assignRole('coordinator');

        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2022-02-16');
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_save_annual_review()
    {
        $this->user->revokePermissionTo('groups-manage');

        $annualReview = AnnualReview::factory()->create(['expert_panel_id' => $this->expertPanel->id]);

        $this->makeRequest($annualReview)
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function stores_completed_at_date_when_submitted_by_privilegged_user()
    {
        $annualReview = AnnualReview::factory()
                            ->create([
                                'expert_panel_id' => $this->expertPanel->id,
                                'submitter_id' => $this->coordinator->id,
                            ]);

        $this->makeRequest($annualReview)
            ->assertStatus(200);

        $this->assertDatabaseHas('annual_reviews', [
            'id' => $annualReview->id,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * @test
     */
    public function returns_400_if_not_all_requirements_are_unmet()
    {
        $annualReview = AnnualReview::create(['expert_panel_id'=>$this->expertPanel->id]);
        
        $this->makeRequest($annualReview)
            ->assertStatus(400)
            ->assertJsonFragment(['message' => 'There are unmet requirements.']);
    }

    private function makeRequest($annualReview)
    {
        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/annual-reviews/'.$annualReview->id;
        return $this->json('POST', $url);
    }
}
