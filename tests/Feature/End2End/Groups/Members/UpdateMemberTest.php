<?php

namespace Tests\Feature\End2End\Groups\Members;

use App\Modules\Group\Models\GroupMember;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateMemberTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->setupEntities();
        $this->admin = User::factory()->create();
        $this->groupMember = GroupMember::factory()->create();
        $this->role = config('permission.models.role')::factory()
                        ->create(['scope' => 'group']);
    }


    /**
     * @test
     */
    public function returns_404_if_group_not_found()
    {
        $url = '/api/groups/'.uniqid().'/members/'.uniqid();

        $this->admin->givePermissionTo('groups-manage');

        Sanctum::actingAs($this->admin);
        $response = $this->json(
            'PUT',
            $url,
            []
        );
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function a_unprivileged_user_may_not_update_a_group_member()
    {
        $url = '/api/groups/'.$this->groupMember->group->uuid.'/members/'.$this->groupMember->id;

        Sanctum::actingAs($this->admin);
        $response = $this->json(
            'PUT',
            $url,
            ['is_contact' => 1]
        );
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function a_privileged_use_may_update_a_group_member()
    {
        $this->admin->givePermissionTo('groups-manage');
        $url = '/api/groups/'.$this->groupMember->group->uuid.'/members/'.$this->groupMember->id;
        Sanctum::actingAs($this->admin);

        $response = $this->json(
            'PUT',
            $url,
            ['is_contact' => 1]
        );

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $this->groupMember->id,
            'is_contact' => true
        ]);

        $this->assertDatabaseHas('group_members', [
            'person_id' => $this->groupMember->person_id,
            'is_contact' => 1
        ]);
    }
    
    /**
     * @test
     */
    public function logs_update()
    {
        $this->admin->givePermissionTo('groups-manage');
        $url = '/api/groups/'.$this->groupMember->group->uuid.'/members/'.$this->groupMember->id;
        Sanctum::actingAs($this->admin);

        $response = $this->json(
            'PUT',
            $url,
            ['is_contact' => 1]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'activity_type' => 'member-updated',
            'subject_id' => $this->groupMember->group_id
        ]);
    }
    

    private function callUpdateEndpoint($groupMemberId, ?array $data = null)
    {
        $data = $data ?? [
            'is_contact' => false
        ];

        $url = '/api/groups/'.$this->group->uuid.'/members';

        Sanctum::actingAs($this->admin);
        $response = $this->json(
            'PUT',
            $url,
            $data
        );
        return $response;
    }
}
