<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\Submission;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RejectSubmissionTest extends TestCase
{
    use RefreshDatabase;

    const NOTE = 'This is a note about the don\'t-call-it-a-rejection.';

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();    
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->admin = $this->setupUserWithPerson(null, ['ep-applications-manage']);
        
        (new SubmissionTypeAndStatusSeeder)->run();
        $this->submission = Submission::factory()
                                ->create([
                                    'group_id' => $this->expertPanel->group_id,
                                    'submission_type_id' => config('submissions.types.application.definition.id'),
                                    'submitter_id' => $this->admin->person->id
                                ]);
        
        Sanctum::actingAs($this->admin);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_reject_submission()
    {
        $this->admin->revokePermissionTo('ep-applications-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_reject_a_submission()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->submission->id,
                'submission_status_id' => config('submissions.statuses.revise-and-resubmit.id'),
                'notes' => static::NOTE
            ]);
        
        $this->assertDatabaseHas('submissions', [
            'id' => $this->submission->id,
            'submission_status_id' => config('submissions.statuses.revise-and-resubmit'),
            'notes' => static::NOTE
        ]);
    }

    /**
     * @test
     */
    public function emails_group_contacts_when_specified()
    {
        $this->makeRequest();
    }
    
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
                            'notify_contacts' => true,
                            'notes' => static::NOTE
                         ];
        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/application/submission/'.$this->submission->id.'/rejection', $data);
    }
    
    
    
}
