<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateGroupAttributesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Group $group;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        $this->group = Group::factory()->create(['group_status_id' => config('groups.statuses.active.id')]);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_update_group_attributes()
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
        $this->makeRequest(['name' => $this->getLongString()])
            ->assertStatus(422)
            ->assertJsonFragment([
                'name' => ['The name may not be greater than 255 characters.']
            ]);

        $this->makeRequest(['group_status_id' => -1])
            ->assertStatus(422)
            ->assertJsonFragment([
                'group_status_id' => ['The selection is invalid.']
            ]);

        $this->makeRequest(['parent_id' => -1])
            ->assertStatus(422)
            ->assertJsonFragment([
                'parent_id' => ['The selection is invalid.']
            ]);
    }
    
    /**
     * @test
     */
    public function authorized_user_can_update_group_description()
    {
        $oldDesc = $this->group->description;
        $newDesc = $this->faker->sentence();
        $this->makeRequest(['description' => $newDesc])
            ->assertStatus(200)
            ->assertJsonFragment([
                'description' => $newDesc
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'description' => $newDesc,
        ]);

        $this->assertLoggedActivity(
            subject: $this->group,
            logName: 'groups',
            description: 'Description updated from "'.$oldDesc.'" to "'.$newDesc.'"',
        );
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

    /**
     * @test
     */
    public function logs_status_updated()
    {
        $oldStatus = $this->group->status->name;
        $res = $this->makeRequest(['group_status_id' => config('groups.statuses.inactive.id')]);
        logger($res->getContent());

        $this->assertLoggedActivity(
            subject: $this->group,
            logName: 'groups',
            description: 'Status updated from "'.$oldStatus.'" to "'.config('groups.statuses.inactive.name').'"',
        );
    }
    
    private function makeRequest($data = null)
    {
        $data = $data ?? ['name' => 'New name'];
        return $this->json('PUT', '/api/groups/'.$this->group->uuid, $data);
    }
}
