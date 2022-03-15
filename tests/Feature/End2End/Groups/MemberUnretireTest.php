<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberGrantPermissions;
use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MemberUnretireTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->user = $this->setupUserWithPerson(permissions: ['groups-manage']);
        // $this->group = Group::factory()->create();
        $this->groupMember = GroupMember::factory()->create(['end_date' => Carbon::now()]);

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_unretire_a_member()
    {
        $this->user->revokePermissionTo('groups-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function system_permissioned_user_can_unretire_a_member()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'id' => $this->groupMember->id,
            'group_id' => $this->groupMember->group_id,
            'end_date' => null,
        ]);
    }

    /**
     * @test
     */
    public function membersRetire_permissioned_member_can_unretire_member()
    {
        $this->user->revokePermissionTo('groups-manage');

        $permission = $this->setupPermission('members-retire', 'group');
        $userMember = app()->make(MemberAdd::class)->handle($this->groupMember->group, $this->user->person);
        app()->make(MemberGrantPermissions::class)->handle($userMember, collect([$permission]));

        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'id' => $this->groupMember->id,
            'group_id' => $this->groupMember->group_id,
            'end_date' => null,
        ]);
    }
    
    private function makeRequest()
    {
        return $this->json('post', '/api/groups/'.$this->groupMember->group->uuid.'/members/'.$this->groupMember->id.'/unretire');
    }
}
