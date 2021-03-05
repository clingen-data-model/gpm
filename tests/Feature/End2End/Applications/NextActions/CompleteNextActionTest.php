<?php

namespace Tests\Feature\End2End\Applications\NextActions;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Models\NextAction;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteNextActionTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->application = Application::factory()->create();
        $this->nextAction = NextAction::factory()->make();
        $this->application->addNextAction($this->nextAction);
        $this->url = 'api/applications/'.$this->application->uuid.'/next-actions/'.$this->nextAction->uuid.'/complete';
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
