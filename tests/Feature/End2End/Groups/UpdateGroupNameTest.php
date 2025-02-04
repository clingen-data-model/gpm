<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class UpdateGroupNameTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        $this->group = Group::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_update_group_name()
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
                'name' => ['This is required.']
            ]);


        $this->makeRequest(['name' => $this->getLongString()])
            ->assertStatus(422)
            ->assertJsonFragment([
                'name' => ['The name may not be greater than 255 characters.']
            ]);
    }
    
    /**
     * @test
     */
    public function authorized_user_can_update_group_name()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'New name'
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'name' => 'New name'
        ]);
    }

    /**
     * @test
     */
    public function logs_name_updated()
    {
        $oldName = $this->group->name;
        $this->makeRequest();

        $this->assertLoggedActivity(
            subject: $this->group,
            logName: 'groups',
            description: 'Name changed from "'.$oldName.'" to "New name"',
        );
    }
    
    private function makeRequest($data = null)
    {
        $data = $data ?? ['name' => 'New name'];
        return $this->json('PUT', '/api/groups/'.$this->group->uuid.'/name', $data);
    }
}
