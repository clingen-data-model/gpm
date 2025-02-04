<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class UpdateGroupDescriptionTest extends TestCase
{
    use FastRefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->expertPanel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);
        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/scope-description';
    }

    /**
     * @test
     */
    public function unprivileged_users_cannot_save_scope_description()
    {
        $unprivileged = User::factory()->create();
        Sanctum::actingAs($unprivileged);
        $this->json('PUT', $this->url, ['description' => 'this is a scope description'])
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_input()
    {
        Sanctum::actingAs($this->user);
        $this->json('PUT', $this->url, [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'description' => ['This field is required.']
            ]);
    }

    /**
     * @test
     */
    public function privileged_user_can_store_scope_description()
    {
        Sanctum::actingAs($this->user);
        $description = 'I am a description of the scope.  I can be greater than 255 characters.I am a description of the scope.  I can be greater than 255 characters.I am a description of the scope.  I can be greater than 255 characters.I am a description of the scope.  I can be greater than 255 characters.';
        $response = $this->json('PUT', $this->url, ['description' => $description]);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'uuid' => $this->expertPanel->group->uuid
        ]);
        $response->assertJsonFragment([
            'uuid' => $this->expertPanel->uuid
        ]);
        $response->assertJsonFragment([
            'scope_description' => $description
        ]);
    }
    
    /**
     * @test
     */
    public function logs_activity()
    {
        $description = 'Test description';
        Sanctum::actingAs($this->user);
        $response = $this->json('PUT', $this->url, ['description' => $description]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Group::class,
            'subject_id' => $this->expertPanel->group_id,
            'activity_type' => 'group-description-updated',
            'description' => 'Group description updated.',
            'properties->description' => $description,
        ]);
    }
    
}
