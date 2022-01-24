<?php

namespace Tests\Feature\End2End\Groups;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateAnnualReviewsForAllTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->expertPanel1 = ExpertPanel::factory()->create(['step_1_approval_date' => '2020-01-01']);
        $this->expertPanel2 = ExpertPanel::factory()->create(['step_1_approval_date' => '2020-01-01']);
    }

    /**
     * @test
     */
    public function can_create_annual_reviews_for_all_expert_panels_via_artisan()
    {
        $start = Carbon::tomorrow()->format('Y-m-d');
        $end = Carbon::today()->addDays(7)->format('Y-m-d');
        $this->artisan('annual-reviews:init-window')
            ->expectsQuestion('What year does the window cover?', (Carbon::now()->year-1))
            ->expectsQuestion('When does the review window begin?', $start)
            ->expectsQuestion('When does the review window end?', $end)
            ->expectsOutput('The annual review window is scheduled for '.$start.' to '.$end.'.')
            ->expectsOutput('Annual reviews created for 2 expert panels.');

        $this->assertDatabaseHas('annual_reviews', [
            'expert_panel_id' => $this->expertPanel1->id
        ]);
        $this->assertDatabaseHas('annual_reviews', [
            'expert_panel_id' => $this->expertPanel2->id
        ]);

        $this->assertDatabaseHas('annual_review_windows', [
            'start' => $start.' 00:00:00',
            'end' => $end.' 00:00:00'
        ]);
    }
}
