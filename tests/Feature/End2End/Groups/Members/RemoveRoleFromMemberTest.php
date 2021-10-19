<?php

namespace Tests\Feature\End2End\Groups\Members;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Group\Actions\MemberAssignRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group groups
 * @group members
 */
class RemoveRoleFromMemberTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->roles = config('permission.models.role')::factory(2)->create(['scope' => 'group']);

        $this->setupEntities()->setupMember();

        MemberAssignRole::run($this->groupMember, $this->roles);
        $this->url = 'api/groups/'.$this->group->uuid.'/members/'.$this->groupMember->id.'/roles';
    }

    /**
     * @test
     */
    public function can_remove_a_role_from_a_group_member()
    {
        // (new MemberRemoveRole)->handle($this->groupMember, [$this->roles->first()]);
        Sanctum::actingAs($this->user);
        $this->json('DELETE', $this->url.'/'.$this->roles->first()->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('model_has_roles', [
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
    public function logs_role_removed_activity()
    {
        Sanctum::actingAs($this->user);
        $this->json('DELETE', $this->url.'/'.$this->roles->first()->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => get_class($this->group),
            'subject_id' => $this->group->id,
            'activity_type' => 'member-role-removed',
            'description' => 'Removed role '.$this->roles->first()->name.' from member '.$this->person->name.'.',
            'properties->group_member_id' => $this->groupMember->id,
            'properties->role->id' => $this->roles->first()->id,
            'properties->role->name' => $this->roles->first()->name,
            'properties->person->name' => $this->person->first()->name,
            'properties->person->id' => $this->person->first()->id,
            'properties->person->email' => $this->person->first()->email,
        ]);
    }
}
