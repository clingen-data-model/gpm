<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Modules\Group\Models\GroupMember;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class UpdateGroupNameTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        $this->group = Group::factory()->create();
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function unauthorized_user_cannot_update_group_name()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    #[Test]
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
    
    #[Test]
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

    #[Test]
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

    #[Test]
    public function coordinator_can_update_group_name()
    {
        $coordinator = $this->setupUserWithPerson();
        $permission = Permission::factory()->create(['name' => 'info-edit', 'scope' => 'group']);
        $role = Role::where('name', 'coordinator')->firstOrFail();
        $role->givePermissionTo($permission);
        $member = GroupMember::factory()->create([
            'group_id' => $this->group->id,
            'person_id' => $coordinator->person->id,
        ]);
        $member->roles()->attach($role->id);
        $this->actingAs($coordinator);

        $this->makeRequest(['name' => 'Coordinator name'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Coordinator name']);

        $this->assertSame('Coordinator name', $this->group->fresh()->name);
    }

    #[Test]
    public function expert_panel_name_activity_contains_the_previous_and_new_name()
    {
        $panel = ExpertPanel::factory()->gcep()->create([
            'long_base_name' => 'Old EP Name',
            'short_base_name' => 'EP',
        ]);

        $this->json('PUT', '/api/groups/'.$panel->group->uuid.'/expert-panel/name', [
            'long_base_name' => 'New EP Name',
            'short_base_name' => 'EP',
        ])->assertOk();

        $this->assertLoggedActivity(
            subject: $panel->group,
            logName: 'groups',
            description: 'EP name updated from "Old EP Name" to "New EP Name".',
        );
    }
    
    private function makeRequest($data = null)
    {
        $data = $data ?? ['name' => 'New name'];
        return $this->json('PUT', '/api/groups/'.$this->group->uuid.'/name', $data);
    }
}
