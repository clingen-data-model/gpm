<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateGroupParentTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        Sanctum::actingAs($this->user);

        $this->parent = Group::factory()->create();
        $this->group = Group::factory()->create();
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_parent(): void
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_parameters(): void
    {
        $this->makeRequest([])
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function privileged_user_can_update_group_parent(): void
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'parent_id' => $this->parent->id,
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'parent_id' => $this->parent->id,
        ]);
    }

    /**
     * @test
     */
    public function nulls_parent_id_if_parent_id_is_0(): void
    {
        $parent = Group::factory()->create();
        $this->group->update(['parent_id' => $parent->id]);

        $response = $this->makeRequest(['parent_id' => 0])
            ->assertStatus(200)
            ->assertJsonFragment([
                'parent_id' => null,
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'parent_id' => null,
        ]);
    }

    /**
     * @test
     */
    public function logs_activity(): void
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
