<?php

namespace Tests\Feature\End2End\Groups\Members;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Group\Models\Group;

/**
 * @group groups
 * @group members
 */
class RevokeMemberPermissionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        
        $this->setupEntities()->setupMember();
        $this->permissions = config('permission.models.permission')::factory(2)->create(['scope' => 'group']);
        MemberGrantPermissions::run($this->groupMember, $this->permissions);

        $this->url = '/api/groups/'.$this->group->uuid.'/members/'.$this->groupMember->id.'/permissions';
    }
    
    /**
     * @test
     */
    public function can_revoke_a_permission()
    {
        $response = $this->json('DELETE', $this->url.'/'.$this->permissions->first()->id);
        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $this->groupMember->id,
            'person_id' => $this->person->id,
            'group_id' => $this->group->id
        ]);

        $this->assertDatabaseMissing('model_has_permissions', [
            'model_type' => GroupMember::class,
            'model_id' => $this->groupMember->id,
            'permission_id' => $this->permissions->first()->id
        ]);

        $this->assertDatabaseHas('model_has_permissions', [
            'model_type' => GroupMember::class,
            'model_id' => $this->groupMember->id,
            'permission_id' => $this->permissions->last()->id
        ]);
    }

    /**
     * @test
     */
    public function logs_permission_revoked_activity()
    {
        $response = $this->json('DELETE', $this->url.'/'.$this->permissions->first()->id);
        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Group::class,
            'subject_id' => $this->group->id,
            'activity_type' => 'member-permission-revoked',
            'description' => 'Permission '.$this->permissions->first()->name.' revoked from member '.$this->person->name.'.',
            'properties->group_member_id' => $this->groupMember->id,
            'properties->permission->id' => $this->permissions->first()->id,
            'properties->permission->name' => $this->permissions->first()->name
        ]);
    }
}
