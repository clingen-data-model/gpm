<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Tests\TestCase;
use App\Models\NextAction;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Jobs\CreateNextAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Application\Events\NextActionAdded;

class CreateNextActionTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->application = Application::factory()->create();
    }
    
    /**
     * @test
     */
    public function raises_NextActionAdded_event()
    {
        $nextAction = NextAction::factory()->make();
        Event::fake();
        Bus::dispatch(new CreateNextAction(
            applicationUuid: $this->application->uuid,
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
            applicationUuid: $this->application->uuid,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step
        ));

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->application->id,
            'description' => 'Added next action: '.$nextAction->entry,
        ]);

        $this->assertEquals(3, $this->application->logEntries->last()->properties['step']);
    }
}
