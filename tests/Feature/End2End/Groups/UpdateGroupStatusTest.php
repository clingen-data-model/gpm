<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class UpdateGroupStatusTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        $this->group = Group::factory()->create(['group_status_id' => 1]);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_update_group_status()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_parameters()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'status_id' => ['This field is required.']
            ]);


        $this->makeRequest(['status_id' => 99999])
            ->assertStatus(422)
            ->assertJsonFragment([
                'status_id' => ['The status you selected is invalid.']
            ]);
    }
    
    /**
     * @test
     */
    public function authorized_user_can_update_group_status()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'group_status_id' => config('groups.statuses.active.id')
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'group_status_id' => config('groups.statuses.active.id')
        ]);
    }

    /**
     * @test
     */
    public function logs_status_updated()
    {
        $oldStatus = $this->group->status->name;
        $this->makeRequest();

        $this->assertLoggedActivity(
            subject: $this->group,
            logName: 'groups',
            description: 'Status updated from "'.$oldStatus.'" to "'.config('groups.statuses.active.name').'"',
        );
    }
    
    private function makeRequest($data = null)
    {
        $data = $data ?? ['status_id' => config('groups.statuses.active.id')];
        return $this->json('PUT', '/api/groups/'.$this->group->uuid.'/status', $data);
    }
}
