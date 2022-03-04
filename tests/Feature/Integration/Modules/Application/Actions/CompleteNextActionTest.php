<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\NextActionAssigneesTableSeeder;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\ExpertPanel\Actions\NextActionComplete;
use App\Modules\ExpertPanel\Events\NextActionCompleted;

class CompleteNextActionTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(NextActionAssigneesTableSeeder::class);

        $this->expertPanel = ExpertPanel::factory()->create();
    }

    /**
     * @test
     */
    public function raises_NextActionCompleted_event()
    {
        $nextAction = NextAction::factory()->make();
        NextActionCreate::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step
        );

        Event::fake();

        NextActionComplete::run(
            expertPanelUuid: $this->expertPanel->uuid,
            nextActionUuid: $nextAction->uuid,
            dateCompleted: '2021-02-01'
        );

        Event::assertDispatched(NextActionCompleted::class);
    }
    
    /**
     * @test
     */
    public function NextActionCompleted_logged()
    {
        $nextAction = NextAction::factory()->make();
        NextActionCreate::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step
        );

        NextActionComplete::run(
            expertPanelUuid: $this->expertPanel->uuid,
            nextActionUuid: $nextAction->uuid,
            dateCompleted: '2021-02-01'
        );

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->group->id,
            'description' => 'Next action completed: '.$nextAction->entry
        ]);
    }
}
