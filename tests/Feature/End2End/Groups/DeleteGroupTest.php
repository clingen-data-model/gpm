<?php

namespace Tests\Feature\End2End\Groups;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteGroupTest extends TestCase
{
    use RefreshDatabase;

    private $user, $group;

    public function setup():void
    {
        parent::setup();
        Carbon::setTestNow();
        $this->setupPermission('groups-manage');
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions:['groups-manage']);
        $this->group = Group::factory()->create();

        Sanctum::actingAs($this->user);
    }
    
    /**
     * @test
     */
    public function unprivileged_user_cannot_delete_group()
    {
        $this->user->revokePermissionTo('groups-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_delete_group()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertSoftDeleted($this->group);
    }

    /**
     * @test
     */
    public function group_memberships_are_marked_deleted_when_group_deleted()
    {
        $groupMember = GroupMember::factory()->create(['group_id' => $this->group->id]);

        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'id' => $groupMember->id,
            'group_id' => $this->group->id,
            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function group_expert_panel_and_ep_is_deleted()
    {
        $expertPanel = ExpertPanel::factory()->create(['group_id' => $this->group->id]);

        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $expertPanel->id,
            'deleted_at' => Carbon::now()
        ]);
    }
    

    private function makeRequest()
    {
        return $this->json('DELETE', '/api/groups/'.$this->group->uuid);
    }
}
