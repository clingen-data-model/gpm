<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Tests\TestCase;
use App\Models\NextAction;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Jobs\CreateNextAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Events\NextActionAdded;

/**
 * @group next-actions
 */
class CreateNextActionTest extends TestCase
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
    public function raises_NextActionAdded_event()
    {
        $nextAction = NextAction::factory()->make();
        Event::fake();
        Bus::dispatch(new CreateNextAction(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step
        ));

        Event::assertDispatched(NextActionAdded::class);
    }

    /**
     * @test
     */
    public function logs_next_action_added()
    {
        $nextAction = NextAction::factory()->make(['step'=>3]);
        Bus::dispatch(new CreateNextAction(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step,
            assignedTo: 'CDWG OC',
            assignedToName: 'Bob Dobbs'
        ));

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->id,
            'description' => 'Added next action: '.$nextAction->entry,
            'properties->next_action->assigned_to' => 'CDWG OC',
            'properties->next_action->assigned_to_name' => 'Bob Dobbs'
        ]);

        $this->assertEquals(3, $this->expertPanel->logEntries->last()->properties['step']);
    }
}
