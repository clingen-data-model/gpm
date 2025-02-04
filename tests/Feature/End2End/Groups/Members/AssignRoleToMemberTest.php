<?php

namespace Tests\Feature\End2End\Groups\Members;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\User\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group groups
 * @group members
 */
class AssignRoleToMemberTest extends TestCase
{
    use FastRefreshDatabase;
    use SetsUpGroupPersonAndMember;

    private User $user;
    private Group $group;
    private Person $person;
    private $url;
    private $groupMember;
    private $roles;
    
    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        $this->setupEntities()->setupMember();
        
        $this->roles = config('permission.models.role')::factory(2)->create(['scope' => 'group']);

        $this->url = 'api/groups/'.$this->group->uuid.'/members/'.$this->groupMember->id.'/roles';
    }

    /**
     * @test
     */
    public function can_assign_group_roles_to_a_group_member()
    {
        $this->withoutExceptionHandling();

        Sanctum::actingAs($this->user);
        $response = $this->json(
            'POST',
            $this->url,
            [
                'role_ids' => $this->roles->pluck('id')->toArray()
            ]
        );
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'group_id' => $this->group->id,
            'person_id' => $this->person->id
        ]);
        $response->assertJsonFragment([
            'name' => $this->roles->first()->name,
        ]);
        $response->assertJsonFragment([
            'name' => $this->roles->last()->name,
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => get_class($this->groupMember),
            'model_id' => $this->groupMember->id,
            'role_id' => $this->roles->first()->id
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => get_class($this->groupMember),
            'model_id' => $this->groupMember->id,
            'role_id' => $this->roles->last()->id
        ]);
    }

    /**
     * @test
     */
    public function validates_role_ids_are_present()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json(
            'POST',
            $this->url,
            []
        );
        $response->assertStatus(422);

        $response->assertJsonFragment(['role_ids' => ['This is required.']]);
    }

    /**
     * @test
     */
    public function validates_that_all_roles_are_scoped_to_group()
    {
        $systemRole = config('permission.models.role')::factory()->create(['scope' => 'system']);

        Sanctum::actingAs($this->user);
        $response = $this->json('POST', $this->url, ['role_ids' => [$this->roles->first()->id, $systemRole->id]])
                        ->assertStatus(422);

        $response->assertJsonFragment = [
            'role_ids' => ['All roles must be group roles.']
        ];
    }

    /**
     * @test
     */
    public function logs_member_role_assignment_activity()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json(
            'POST',
            $this->url,
            [
                'role_ids' => $this->roles->pluck('id')->toArray()
            ]
        );

        $this->groupMember = $this->groupMember->fresh();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Group::class,
            'subject_id' => $this->groupMember->group->id,
            'activity_type' => 'member-role-assigned',
            'properties->member->id' => $this->groupMember->person->id,
            'properties->member->name' => $this->groupMember->person->name,
            'properties->member->email' => $this->groupMember->person->email,
            'properties->roles' => json_encode($this->groupMember->roles->pluck('name')->toArray())
        ]);
    }
}
