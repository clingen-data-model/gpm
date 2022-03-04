<?php

namespace Tests\Feature\End2End\ExpertPanels\NextActions;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\NextActionAssigneesTableSeeder;
use App\Modules\ExpertPanel\Actions\NextActionCreate;

/**
 * @group next-actions
 * @group next_actions
 * @group nextactions
 */
class CompleteNextActionTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder([NextActionAssigneesTableSeeder::class]);

        $this->user = $this->setupUser();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->nextAction = NextAction::factory()->make();

        NextActionCreate::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $this->nextAction->uuid,
            dateCreated: $this->nextAction->date_created,
            entry: $this->nextAction->entry,
            dateCompleted: $this->nextAction->dateCompleted,
            targetDate: $this->nextAction->targetDate,
            step: $this->nextAction->step
        );
        $this->url = 'api/applications/'.$this->expertPanel->uuid.'/next-actions/'.$this->nextAction->uuid.'/complete';
    }

    /**
     * @test
     */
    public function can_mark_next_action_complete()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', $this->url, ['date_completed' => '2021-01-01'])
            ->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $this->nextAction->uuid,
                'date_completed' => Carbon::parse('2021-01-01')->toJson()
            ]);
    }
}
