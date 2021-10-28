<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateMembershipDescriptionTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->expertPanel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('ep-applications-manage');
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/application/membership-description';
    }

    /**
     * @test
     */
    public function unprivileged_users_cannot_save_membership_description()
    {
        $unprivileged = User::factory()->create();
        Sanctum::actingAs($unprivileged);
        $this->json('PUT', $this->url, ['membership_description' => 'this is a membership description'])
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
                'membership_description' => ['This field is required.']
            ]);
    }

    /**
     * @test
     */
    public function privileged_user_can_store_membership_description()
    {
        Sanctum::actingAs($this->user);
        $description = 'I am a description of the membership.  I can be greater than 255 characters.I am a description of the membership.  I can be greater than 255 characters.I am a description of the membership.  I can be greater than 255 characters.I am a description of the membership.  I can be greater than 255 characters.';
        $response = $this->json('PUT', $this->url, ['membership_description' => $description]);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'uuid' => $this->expertPanel->group->uuid
        ]);
        $response->assertJsonFragment([
            'uuid' => $this->expertPanel->uuid
        ]);
        $response->assertJsonFragment([
            'membership_description' => $description
        ]);
    }
    
    /**
     * @test
     */
    public function logs_activity()
    {
        $description = 'Test description';
        Sanctum::actingAs($this->user);
        $response = $this->json('PUT', $this->url, ['membership_description' => $description]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Group::class,
            'subject_id' => $this->expertPanel->group_id,
            'activity_type' => 'membership-description-updated',
            'description' => 'Membership description updated.',
            'properties->membership_description' => $description,
        ]);
    }
    
    /**
     * @test
     */
    public function throws_validation_error_if_group_is_not_an_expert_panel()
    {
        $this->expertPanel->group->update(['group_type_id' => config('expert_panels.types.gcep.id')]);

        Sanctum::actingAs($this->user);
        $response = $this->json('PUT', $this->url, ['membership_description' => 'test test test']);

        $response->assertStatus(422);

        $response->assertJsonFragment([
            'membership_description' => ['A membership description can only be set for VCEPs.']
        ]);
    }
    
    
}
