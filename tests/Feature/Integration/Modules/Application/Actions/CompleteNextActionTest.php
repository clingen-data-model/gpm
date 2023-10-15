<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use App\Modules\ExpertPanel\Actions\NextActionComplete;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\ExpertPanel\Events\NextActionCompleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\NextAction;
use Carbon\Carbon;
use Database\Seeders\NextActionAssigneesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompleteNextActionTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(NextActionAssigneesTableSeeder::class);

        $this->expertPanel = ExpertPanel::factory()->create();
    }

    /**
     * @test
     */
    public function raises_NextActionCompleted_event(): void
    {
        $nextAction = NextAction::factory()->make();
        NextActionCreate::run(
            expertPanel: $this->expertPanel,
            uuid: $nextAction->uuid,
            dateCreated: $nextAction->date_created,
            entry: $nextAction->entry,
            dateCompleted: $nextAction->dateCompleted,
            targetDate: $nextAction->targetDate,
            step: $nextAction->step
        );

        Event::fake();

        NextActionComplete::run(
            expertPanel: $this->expertPanel,
            nextAction: $nextAction,
            dateCompleted: '2021-02-01'
        );

        Event::assertDispatched(NextActionCompleted::class);
    }

    /**
     * @test
     */
    public function NextActionCompleted_logged(): void
    {
        $nextAction = NextActionCreate::run(
            expertPanel: $this->expertPanel,
            dateCreated: Carbon::now(),
            entry: 'Some nextAction',
            dateCompleted: Carbon::now(),
            targetDate: Carbon::now()->addDays(14),
        );

        app()->make(NextActionComplete::class)->handle(
            expertPanel: $this->expertPanel,
            nextAction: $nextAction,
            dateCompleted: '2021-02-01'
        );

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->group->id,
            'description' => 'Next action completed: '.$nextAction->entry,
        ]);
    }
}
