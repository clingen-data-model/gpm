<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\NextActionType;
use App\Mail\UserDefinedMailable;
use Illuminate\Support\Facades\Mail;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Submission;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\NextActionTypesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Database\Seeders\NextActionAssigneesTableSeeder;
use App\Modules\ExpertPanel\Models\NextActionAssignee;
use App\Modules\Group\Events\ApplicationRevisionsRequested;

class RejectSubmissionTest extends TestCase
{
    use RefreshDatabase;

    const NOTE = 'This is a note about the don\'t-call-it-a-rejection.';

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(NextActionTypesTableSeeder::class); 
        $this->runSeeder(NextActionAssigneesTableSeeder::class); 
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->admin = $this->setupUserWithPerson(null, ['ep-applications-manage']);
        
        (new SubmissionTypeAndStatusSeeder)->run();
        $this->submission = Submission::factory()
                                ->create([
                                    'group_id' => $this->expertPanel->group_id,
                                    'submission_type_id' => config('submissions.types.application.definition.id'),
                                    'submitter_id' => $this->admin->person->id,
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
        Carbon::setTestNow();
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->submission->id,
                'submission_status_id' => config('submissions.statuses.revise-and-resubmit.id'),
            ]);
        
        $this->assertDatabaseHas('submissions', [
            'id' => $this->submission->id,
            'submission_status_id' => config('submissions.statuses.revise-and-resubmit'),
            'closed_at' => Carbon::now(),
        ]);
    }

    /**
     * @test
     */
    public function emails_group_contacts_when_specified_and_saves_email_body_to_response_content()
    {
        Mail::fake();
        $data = $this->makeDefaultData(['notify_contacts' => true]);

        $person1 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person1->uuid);

        $person2 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person2->uuid);

        $this->makeRequest($data);

        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) use ($data, $person1, $person2) {
                return $mail->subject == $data['subject']
                    && $mail->body == $data['body']
                    && $mail->attachments == []
                    && $mail->hasTo($person1->email)
                    && $mail->hasTo($person2->email)
                ;
            }
        );
        $this->assertDatabaseHas('submissions', [
            'id' => $this->submission->id,
            'submission_status_id' => config('submissions.statuses.revise-and-resubmit.id'),
            'closed_at' => Carbon::now(),
            'response_content' => $data['body']
        ]);

    }

    /**
     * @test
     */
    public function records_revisions_requested_activity()
    {
        $this->makeRequest();

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: 'Revisions requested for step '.$this->expertPanel->current_step,
            properties:  [
                'submission_id' => $this->submission->id,
            ],
            activity_type: 'application-revisions-requested',
            logName: 'groups'
        );
    }
    
    /**
     * @test
     */
    public function assigns_make_revisions_next_action_to_expert_panel()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('next_actions', [
            'expert_panel_id' => $this->expertPanel->id,
            'type_id' => config('next_actions.types.make-revisions'),
            'assignee_id' => config('next_actions.assignees.expert-panel.id')
        ]);
    }
    
    /**
     * @test
     */
    public function completes_review_submission_action_if_any()
    {
        Carbon::setTestNow('2022-06-01');
        $nextAction = NextAction::factory()->create([
            'type_id' => config('next_actions.types.review-submission.id'),
            'expert_panel_id' => $this->expertPanel->id
        ]);

        
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('next_actions', [
            'id' => $nextAction->id,
            'date_completed' => Carbon::now()
        ]);
    }
    
    

    private function makeRequest($data = null)
    {
        $data = $data ?? $this->makeDefaultData();

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/application/submission/'.$this->submission->id.'/rejection', $data);
    }

    private function makeDefaultData($mergeData = [])
    {
        return array_merge([
            'notify_contacts' => false,
            'subject' => 'Revise and resubmit your application for '.$this->expertPanel->group->name,
            'notes' => static::NOTE,
            'body' => static::NOTE
        ], $mergeData);
    }
    
    
    
    
}
