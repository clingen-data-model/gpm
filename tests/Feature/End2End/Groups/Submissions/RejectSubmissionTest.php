<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Carbon\Carbon;
use Tests\TestCase;
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
use PHPUnit\Framework\Attributes\Test;

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

        $this->actingAs($this->admin);
    }

    #[Test]
    public function unprivileged_user_cannot_reject_submission()
    {
        $this->admin->revokePermissionTo('ep-applications-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    #[Test]
    public function permissioned_user_can_reject_a_submission()
    {
        Carbon::setTestNow('2022-07-12');
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->submission->id,
                'submission_status_id' => config('submissions.statuses.revisions-requested.id'),
            ]);

        $this->assertDatabaseHas('submissions', [
            'id' => $this->submission->id,
            'submission_status_id' => config('submissions.statuses.revisions-requested.id'),
            'closed_at' => Carbon::now(),
        ]);
    }

    #[Test]
    public function emails_group_contacts_with_email_body_and_stores_separate_reviewer_note()
    {
        Carbon::setTestNow('2022-07-12');
        Mail::fake();
        $data = $this->makeDefaultData(['notify_contacts' => true, 'response_content' => 'Separate reviewer note']);

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
            'submission_status_id' => config('submissions.statuses.revisions-requested.id'),
            'closed_at' => Carbon::now(),
            'response_content' => $data['response_content']
        ]);
        $this->getJson('/api/groups/'.$this->expertPanel->group->uuid.'/application/review-history')
            ->assertOk()->assertJsonPath('cycles.0.rounds.0.revisions_requested_notes', $data['response_content']);

    }

    #[Test]
    public function records_revisions_requested_activity()
    {
        $this->makeRequest();

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: 'Revisions requested for step '.$this->expertPanel->current_step.":\n".static::NOTE,
            properties:  [
                'submission_id' => $this->submission->id,
            ],
            activity_type: 'application-revisions-requested',
            logName: 'groups'
        );
    }

    #[Test]
    public function assigns_make_revisions_next_action_to_expert_panel()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('next_actions', [
            'expert_panel_id' => $this->expertPanel->id,
            'type_id' => config('next_actions.types.make-revisions.id'),
            'assignee_id' => config('next_actions.assignees.expert-panel.id')
        ]);
    }

    #[Test]
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
            'response_content' => static::NOTE,
            'body' => static::NOTE
        ], $mergeData);
    }

    #[Test]
    public function initial_request_never_falls_back_to_email_and_does_not_rewrite_old_notes(): void
    {
        $old = Submission::factory()->create(['group_id' => $this->expertPanel->group_id,
            'response_content' => '<p>Historical email body</p>']);
        $this->makeRequest($this->makeDefaultData(['response_content' => null, 'body' => 'Email only']))->assertOk();
        $this->assertNull($this->submission->fresh()->response_content);
        $this->assertSame('<p>Historical email body</p>', $old->fresh()->response_content);
    }

    #[Test]
    public function initial_step_four_keeps_existing_response_behavior(): void
    {
        $this->submission->update(['submission_type_id' => config('submissions.types.application.sustained-curation.id')]);
        $this->makeRequest($this->makeDefaultData(['response_content' => 'Separate note', 'body' => 'Step four email']))->assertOk();
        $this->assertSame('Step four email', $this->submission->fresh()->response_content);
    }




}
