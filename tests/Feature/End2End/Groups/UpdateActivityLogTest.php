<?php

namespace Tests\Feature\End2End\Groups;

use App\Models\Activity;
use App\Modules\Group\Models\Group;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group groups
 * @group activity-log
 */
class UpdateActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
        $this->logEntry = Activity::factory()->create([
            'subject_type' => Group::class,
            'subject_id' => $this->group->id
        ]);

        $this->url = '/api/groups/'.$this->group->uuid.'/activity-logs/'.$this->logEntry->id;

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_a_log_entry()
    {
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_update_a_log_entry()
    {
        $this->user->givePermissionTo('groups-manage');

        $this->makeRequest(['entry' => 'farts!', 'log_date' => '2021-12-01T00:00:00'])
            ->assertStatus(200)
            ->assertJsonFragment([
                'description' => 'farts!',
            ]);

        $this->assertDatabaseHas('activity_log', [
            'id' => $this->logEntry->id,
            'properties->log_date' => '2021-12-01T00:00:00'
        ]);
    }

    /**
     * @test
     */
    public function validates_data()
    {
        $this->user->givePermissionTo('groups-manage');

        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'entry' => ['The entry field is required.'],
            ])
            ->assertJsonFragment([
                'log_date' => ['The log date field is required.'],
            ]);

        $this->makeRequest(['log_date'=>'bob'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'log_date' => ['The log date is not a valid date.']
            ]);
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'entry' => 'blah blah blah',
            'log_date' => '2021-11-01T00:00:00',
        ];
        return $this->json('PUT', $this->url, $data);
    }
}
