<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group submissions
 * @group applications
 */
class SubmitApplicationStepTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = $this->setupUserWithPerson(null, ['ep-applications-manage']);
        $this->expertPanel = $this->setupVcep();
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_submit_application()
    {
        $this->user->removeRole('ep-applications-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function a_privileged_user_can_submit_appliation()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'expert_panel_id' => $this->expertPanel->id,
                'status' => config('submissions.statuses.pending'),
                'type' => config('submissions.types.application.definition'),
                'approved_at' => null,
                'notes' => 'Notes from the submitter!',
                'submitter_id' => $this->user->person->id
            ]);
        
        $person = Person::factory()->create([
            'user_id' => $this->user->id
        ]);
        $groupMember = GroupMember::factory()->create([
            'group_id' => $this->expertPanel->id,
            'person_id' => $person->id
        ]);
        $groupMember->assignRole('coordinator');
        
        Sanctum::actingAs($this->groupMember->person->user);
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('submissions', [
            'group_id' => $this->expertPanel->id,
            'status' => config('submissions.statuses.pending'),
            'type' => config('submissions.types.applicaiton.definition'),
            'notes' => 'Notes from the submitter!',
            'submitter_id' => $this->person->id
        ]);
    }

    /**
     * @test
     */
    public function automatically_sets_type_to_match_current_step()
    {
        $this->expertPanel->update(['current_step' => 4]);
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'expert_panel_id' => $this->expertPanel->id,
                'status' => config('submissions.statuses.pending'),
                'type' => config('submissions.types.application.sustained-curation'),
                'approved_at' => null,
                'notes' => 'Notes from the submitter!'
            ]);
    }
    
    
    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'notes' => 'Notes from the submitter!'
        ];

        return $this->json('post', '/api/groups/'.$this->expertPanel->group->uuid.'applicaiton/submission');
    }
    

    private function setupGcep($data = null)
    {
        $data = $data ?? [];
        return ExpertPanel::factory()->gcep()->create();
    }

    private function setupVcep($data = null)
    {
        $data = $data ?? [];
        return ExpertPanel::factory()->vcep()->create($data);
    }
}
