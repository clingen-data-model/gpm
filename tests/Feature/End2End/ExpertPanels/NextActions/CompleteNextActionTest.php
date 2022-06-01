<?php

namespace Tests\Feature\End2End\ExpertPanels\NextActions;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
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
            expertPanel: $this->expertPanel,
            uuid: $this->nextAction->uuid,
            dateCreated: $this->nextAction->date_created,
            entry: $this->nextAction->entry,
            dateCompleted: $this->nextAction->dateCompleted,
            targetDate: $this->nextAction->targetDate,
            step: $this->nextAction->step
        );
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function can_mark_next_action_complete()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $this->nextAction->uuid,
                'date_completed' => Carbon::parse('2021-01-01')->toJson()
            ]);
    }
    
    /**
     * @test
     */
    public function validates_input()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'date_completed' => ['This is required.']
            ]);

        $this->makeRequest(['date_completed' => 'early dog'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'date_completed' => ['The date completed is not a valid date.']
            ]);
    }

    private function makeRequest($data = null)
    {
        $data = $data ?? ['date_completed' => '2021-01-01'];
        return $this->json(
            'POST', 
            'api/applications/'.$this->expertPanel->uuid.'/next-actions/'.$this->nextAction->uuid.'/complete', 
            $data
        );
    }
    
    
}
