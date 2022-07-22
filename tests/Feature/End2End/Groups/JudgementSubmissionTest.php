<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Judgement;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\NextActionTypesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use Database\Seeders\NextActionAssigneesTableSeeder;

class JudgementSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->seed(NextActionAssigneesTableSeeder::class);
        $this->seed(NextActionTypesTableSeeder::class);
        $this->submit = app()->make(ApplicationSubmitStep::class);
        
        $this->user = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        $epAndSub = $this->setupExpertPanelAndSubmission();
        $this->expertPanel = $epAndSub['expertPanel'];
        $this->submission = $epAndSub['submission'];
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function only_users_with_approval_permission_can_submit_a_judgement()
    {
        $this->user->revokePermissionTo('ep-applications-approve');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_judgement_data()
    {
        $this->makeRequest([])
            ->assertValidationErrors([
                'judgement' => ['This is required.']
            ]);

        $this->makeRequest(['judgement' => 'hedgehogs'])
            ->assertValidationErrors([
                'judgement' => ['The selection is invalid.']
            ]);
    }

    /**
     * @test
     */
    public function returns_422_if_no_pending_submission_for_group()
    {
        $this->submission->delete();

        $this->makeRequest()
            ->assertValidationErrors([
                'group' => ['This group does not have a pending submission.']
            ]);
        
    }
    

    /**
     * @test
     */
    public function permissioned_user_can_submit_a_judgement()
    {
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJson([
                'judgement' => 'request-revisions',
                'notes' => 'These are my comments I want to add.',
                'submission_id' => $this->submission->id,
                'person_id' => $this->user->person->id
            ]);

        $this->assertDatabaseHas('judgements', [
            'judgement' => 'request-revisions',
            'notes' => 'These are my comments I want to add.',
            'submission_id' => $this->submission->id,
            'person_id' => $this->user->person->id
        ]);
    }

    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [
            'judgement' => 'request-revisions',
            'judgement_notes' => "These are my comments I want to add.",
            'person_id' => $this->user->person->id
        ];

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/application/judgement', $data);
    }    
    

    private function setupExpertPanelAndSubmission($expertPanel = null, $submitter = null): array
    {
        $expertPanel = $expertPanel ?? ExpertPanel::factory()->vcep()->create();
        $submitter = $submitter ?? Person::factory()->create();
        $submission = $this->submit->handle(group: $expertPanel->group, submitter: $submitter);

        return ['expertPanel' => $expertPanel, 'submission' => $submission];
    }
    
}
