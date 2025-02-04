<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use App\Modules\ExpertPanel\Events\NextActionAdded;
use Database\Seeders\NextActionAssigneesTableSeeder;

/**
 * @group next-actions
 */
class NextActionCreateTest extends TestCase
{
    use FastRefreshDatabase;

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
    public function raises_NextActionAdded_event()
    {
        $nextAction = NextAction::factory()->make();
        Event::fake();
        (new NextActionCreate)->handle(
            expertPanel: $this->expertPanel,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step
        );

        Event::assertDispatched(NextActionAdded::class);
    }

    /**
     * @test
     */
    public function logs_next_action_added()
    {
        $nextAction = NextAction::factory()->make(['step'=>3]);
        (new NextActionCreate)->handle(
            expertPanel: $this->expertPanel,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step,
            assignedTo: 1,
            assignedToName: 'Bob Dobbs'
        );

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->group->id,
            'description' => 'Added next action: '.$nextAction->entry,
            'properties->next_action->assignee_id' => 1,
            'properties->next_action->assignee_name' => 'Bob Dobbs'
        ]);

        $this->assertEquals(3, $this->expertPanel->group->logEntries->last()->properties['step']);
    }
}
