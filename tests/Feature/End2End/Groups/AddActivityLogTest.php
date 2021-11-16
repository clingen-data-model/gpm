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
class AddActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
        $this->url = '/api/groups/'.$this->group->uuid.'/activity-logs/';

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_create_a_log_entry()
    {
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_create_a_log_entry()
    {
        $this->user->givePermissionTo('groups-manage');
        $logEntry = Activity::factory(1)->create();

        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'description' => 'blah blah blah'
            ]);
    }

    /**
     * @test
     */
    public function validates_data()
    {
        $this->user->givePermissionTo('groups-manage');
        $logEntry = Activity::factory(1)->create();

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
        return $this->json('POST', $this->url, $data);
    }
}
