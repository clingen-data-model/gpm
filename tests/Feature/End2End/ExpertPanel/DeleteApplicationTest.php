<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class DeleteExpertPanelTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->application = ExpertPanel::factory()->create();
        $this->url = '/api/applications/'.$this->application->uuid;
    }

    /**
     * @test
     */
    public function user_must_be_authenticated_to_delete()
    {
        $this->json('DELETE', $this->url)
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function authenticated_user_can_delete_application()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json('DELETE', $this->url)
            ->assertStatus(200);
        
        $this->assertDatabaseMissing('applications', [
            'uuid' => $this->application->uuid,
            'deleted_at' => null
        ]);
    }

    /**
     * @test
     */
    public function activity_is_logged_when_application_is_deleted()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json('DELETE', $this->url);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => get_class($this->application),
            'subject_id' => $this->application->id,
            'activity_type' => 'expert-panel-deleted',
            'causer_type' => get_class($this->user),
            'causer_id' => $this->user->id
        ]);
    }
}
