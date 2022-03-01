<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CreateGroupTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unauthoried_user_cannot_create_a_group()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_params()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['This field is required.'],
                    'group_type_id' => ['This field is required.'],
                    'group_status_id' => ['This field is required.'],
                ]
            ]);

        $this->makeRequest([
            'name' => $this->getLongString(),
            'group_type_id' => 999,
            'group_status_id' => 999
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'name' => ['The name may not be greater than 255 characters.'],
                // 'group_type_id' => ['The selection is invalid.'],
                'group_status_id' => ['The selection is invalid.'],
            ]
        ]);
    }

    /**
     * @test
     */
    public function authorized_user_can_create_a_new_group()
    {
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Test '.strtoupper(config('groups.types.wg.name')).' Group',
                'group_type_id' => config('groups.types.wg.id'),
                'group_status_id' => config('groups.statuses.active.id'),
                'parent_id' => null
            ]);
        
        $this->assertDatabaseHas('groups', [
            'name' => 'Test '.strtoupper(config('groups.types.wg.name')).' Group',
            'group_type_id' => config('groups.types.wg.id'),
            'group_status_id' => config('groups.statuses.active.id'),
            'parent_id' => null
        ]);
    }

    /**
     * @test
     */
    public function creates_expert_panel_if_group_type_is_ep()
    {
        $response = $this->makeRequest([
            'name' => 'Test EP',
            'group_status_id' => config('groups.statuses.active.id'),
            'group_type_id' => 3,
        ])
            ->assertStatus(201);
        $this->assertNotNull($response->original->expertPanel);

        $this->assertDatabaseHas('expert_panels', [
            'expert_panel_type_id' => config('expert_panels.types.gcep.id'),
            'group_id' => $response->original->id
        ]);
    }

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'name' => 'Test '.strtoupper(config('groups.types.wg.name')).' Group',
            'group_type_id' => config('groups.types.wg.id'),
            'group_status_id' => config('groups.statuses.active.id'),
            'parent_id' => null
        ];

        return $this->json('POST', '/api/groups/', $data);
    }
}
