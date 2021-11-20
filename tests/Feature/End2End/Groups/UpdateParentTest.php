<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateParentTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        Sanctum::actingAs($this->user);

        $this->parent = Group::factory()->create();
        $this->group = Group::factory()->create();
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_parent()
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
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function privileged_user_can_update_group_parent()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'parent_id' => $this->parent->id
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'parent_id' => $this->parent->id
        ]);
    }

    /**
     * @test
     */
    public function logs_activity()
    {
        $oldParent = Group::factory()->create();
        $this->group->update(['parent_id' => $oldParent->id]);

        $this->makeRequest();


        $this->assertLoggedActivity(
            subject: $this->group,
            description: 'Parent changed from '.$oldParent->name.' to '.$this->parent->name.'.',
            logName: 'groups',
        );
    }
    
    
    private function makeRequest($data = null)
    {
        $data = $data ?? ['parent_id' => $this->parent->id];

        return $this->json('PUT', '/api/groups/'.$this->group->uuid.'/parent', $data);
    }
}
