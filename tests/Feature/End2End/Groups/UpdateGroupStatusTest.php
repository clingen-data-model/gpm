<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateGroupStatusTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        $this->group = Group::factory()->create(['group_status_id' => 1]);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_update_group_status(): void
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
            ->assertStatus(422)
            ->assertJsonFragment([
                'status_id' => ['This field is required.'],
            ]);

        $this->makeRequest(['status_id' => 99999])
            ->assertStatus(422)
            ->assertJsonFragment([
                'status_id' => ['The status you selected is invalid.'],
            ]);
    }

    /**
     * @test
     */
    public function authorized_user_can_update_group_status(): void
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'group_status_id' => config('groups.statuses.active.id'),
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'group_status_id' => config('groups.statuses.active.id'),
        ]);
    }

    /**
     * @test
     */
    public function logs_status_updated(): void
    {
        $oldStatus = $this->group->status->name;
        $this->makeRequest();

        $this->assertLoggedActivity(
            subject: $this->group,
            logName: 'groups',
            description: 'Status updated from "'.$oldStatus.'" to "'.config('groups.statuses.active.name').'"',
        );
    }

    private function makeRequest($data = null)
    {
        $data = $data ?? ['status_id' => config('groups.statuses.active.id')];

        return $this->json('PUT', '/api/groups/'.$this->group->uuid.'/status', $data);
    }
}
