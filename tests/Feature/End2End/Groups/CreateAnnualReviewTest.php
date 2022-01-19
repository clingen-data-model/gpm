<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\AnnualReviewCreate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateAnnualReviewTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->expertPanel = ExpertPanel::factory()->create();
    }

    /**
     * @test
     */
    public function it_creates_an_annual_review_for_the_expert_panel()
    {
        $action = new AnnualReviewCreate();

        $action->handle($this->expertPanel);

        $this->assertDatabaseHas('annual_reviews', [
            'expert_panel_id' => $this->expertPanel->id,
        ]);
    }

    /**
     * @test
     */
    public function unpriveleged_user_cannot_create_via_post()
    {
        $user = $this->setupUser();
        Sanctum::actingAs($user);

        $this->makeRequest()
            ->assertStatus(403);

        $this->assertDatabaseMissing('annual_reviews', [
            'expert_panel_id' => $this->expertPanel->id,
        ]);
    }


    /**
     * @test
     */
    public function priveleged_user_can_create_via_post()
    {
        $user = $this->setupUser(permissions: ['groups-manage']);
        Sanctum::actingAs($user);

        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'expert_panel_id' => $this->expertPanel->id
            ]);

        $this->assertDatabaseHas('annual_reviews', [
            'expert_panel_id' => $this->expertPanel->id,
        ]);
    }

    private function makeRequest()
    {
        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/annual-reviews');
    }
}
