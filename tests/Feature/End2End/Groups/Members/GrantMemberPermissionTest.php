<?php

namespace Tests\Feature\End2End\Groups\Members;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\End2End\Groups\Members\SetsUpGroupPersonAndMember;

/**
 * @group groups
 * @group members
 */
class GrantMemberPermissionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->permissions = config('permission.models.permission')::factory(2)->create(['scope' => 'group']);

        $this->setupEntities()->setupMember();

        $this->url = 'api/groups/'.$this->group->uuid.'/members/'.$this->groupMember->id.'/permissions/';
        Sanctum::actingAs($this->user);
    }
    
    /**
     * @test
     */
    public function can_grant_group_permissions_to_group_member()
    {
        $response = $this->json('POST', $this->url, ['permission_ids' => $this->permissions->pluck('id')->toArray()]);
        $response->assertStatus(200);

        foreach ($this->permissions as $perm) {
            // $response->assetJsonFragment([
            //     'id' => $this->groupMember->id,
            //     'person_id' => $this->person->id,
            //     'group_id' => $this->group->id,
            //     'permissions' => [
            //         [
            //             'id' => $perm->id,
            //             'name' => $perm->name
            //         ]
            //     ]
            // ]);

            $this->assertDatabaseHas('model_has_permissions', [
                 'model_id' => $this->groupMember->id,
                 'model_type' => GroupMember::class,
                 'permission_id' => $perm->id
            ]);
        }
    }

    /**
     * @test
     */
    public function logs_permission_granted_to_member()
    {
        $response = $this->json('POST', $this->url, ['permission_ids' => $this->permissions->pluck('id')->toArray()]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
             'activity_type' => 'member-permissions-granted',
             'subject_type' => Group::class,
             'subject_id' => $this->group->id,
             'description' => $this->person->name.' granted permissions '.$this->permissions->pluck('name')->join(', ', ', and '),
             'properties->group_member_id' => $this->groupMember->id,
             'properties->permissions' => json_encode($this->permissions->map(fn ($p) => $p->only('id', 'name'))),
             'properties->person->id' => $this->person->id
        ]);
    }
    
    
    /**
     * @test
     */
    public function validates_required_params()
    {
        $response = $this->json('POST', $this->url, []);
        $response->assertStatus(422);

        $response->assertJsonFragment([
            'permission_ids' => ['The permission ids field is required.']
        ]);
    }

    /**
     * @test
     */
    public function validates_permission_ids_is_array()
    {
        $response = $this->json('POST', $this->url, ['permission_ids' => 1]);
        $response->assertStatus(422);

        $response->assertJsonFragment([
            'permission_ids' => ['The permission ids must be an array.']
        ]);
    }

    /**
     * @test
     */
    public function validates_permissions_exist()
    {
        $response = $this->json('POST', $this->url, ['permission_ids' => [666]]);
        $response->assertStatus(422);

        $response->assertJsonFragment([
            'permission_ids' => ['The selected permission ids is invalid.']
        ]);
    }
    
    
    
    /**
     * @test
     */
    public function validates_permission_is_scoped_to_group()
    {
        $sysPerm = config('permission.models.permission')::factory()->create(['scope' => 'system']);
        $response = $this->json('POST', $this->url, ['permission_ids' => [$sysPerm->id]]);
        $response->assertStatus(422);

        $response->assertJsonFragment = [
            'role_ids' => ['All permissions must be group permissions.']
        ];
    }
}
