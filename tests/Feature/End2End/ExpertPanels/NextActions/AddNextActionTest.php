<?php

namespace Tests\Feature\End2End\ExpertPanels\NextActions;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Factories\NextActionAssigneeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\NextActionAssigneesTableSeeder;
use App\Modules\ExpertPanel\Models\NextActionAssignee;

/**
 * @group next-actions
 * @group next_actions
 * @group nextactions
 */
class AddNextActionTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder([NextActionAssigneesTableSeeder::class]);

        $this->user = $this->setupUser();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->baseUrl = 'api/applications/'.$this->expertPanel->uuid.'/next-actions';
        Carbon::setTestNow();
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function user_can_create_next_action()
    {
        $this->json('POST', $this->baseUrl, [
                'uuid' => Uuid::uuid4()->toString(),
                'date_created' => Carbon::today(),
                'target_date' => Carbon::today()->addDays(7),
                'step' => 1,
                'entry' => 'This is an action I would like to take withing 7 days.',
                'date_completed' => null,
                'assigned_to' => 1,
                'assigned_to_name' => 'Bob Dobbs'
            ])
            ->assertStatus(200)
            ->assertJson([
                'date_created' => Carbon::today()->toJson(),
                'target_date' => Carbon::today()->addDays(7)->toJson(),
                'step' => 1,
                'entry' => 'This is an action I would like to take withing 7 days.',
                'date_completed' => null,
                'assigned_to' => 1,
                'assigned_to_name' => 'Bob Dobbs'
            ]);
    }

    /**
     * @test
     */
    public function validates_required_fields()
    {
        $this->json('POST', $this->baseUrl, [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'uuid' => ['This is required.'],
                'date_created' => ['This is required.'],
                'entry' => ['This is required.'],
                'assigned_to' => ['This is required.']
            ]);
    }

    /**
     * @test
     */
    public function validates_field_types()
    {
        $this->json('POST', $this->baseUrl, [
                'uuid' => 'eat-my-shorts',
                'date_created' => 'test',
                'target_date' => 'test',
                'step' => 'one',
                'date_completed' => 'test',
                'assigned_to' => 'Bob'
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'uuid' => ['The uuid must be a valid UUID.'],
                'date_created' => ['The date created is not a valid date.'],
                'target_date' => ['The target date is not a valid date.'],
                'date_completed' => ['The date completed is not a valid date.'],
                'step' => ['The step must be an integer.'],
                'assigned_to' => ['The next action must be assigned to '.NextActionAssignee::all()->pluck('name')->join(', ', ', or ')]
            ]);
    }
}
