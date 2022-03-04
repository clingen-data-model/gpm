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
class ListActivityLogsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUserWithPerson(permissions: ['groups-manage']);
        $this->group = Group::factory()->create();
        $this->url = '/api/groups/'.$this->group->uuid.'/activity-logs/';

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_get_group_activity_log()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->json('GET', $this->url)
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_get_group_activity_logs()
    {
        $logEntries = Activity::factory(3)->create();

        $response = $this->json('GET', $this->url)
            ->assertStatus(200);
    }
}
