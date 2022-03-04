<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Mail\UserDefinedMailable;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Submission;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApproveStepTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupPermission(['ep-applications-manage']);
        $this->setupForGroupTest();
        $this->runSeeder(SubmissionTypeAndStatusSeeder::class);
        
        $this->person = Person::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        ContactAdd::run($this->expertPanel->uuid, $this->person->uuid);

        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function can_approve_step()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
        ];

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200)
            ->assertJson($this->expertPanel->fresh()->toArray());

        $this->assertDatabaseHas('expert_panels', [
            'uuid' => $this->expertPanel->uuid,
            'current_step' => (string)2,
            'step_1_approval_date' => $approvalData['date_approved'],
        ]);
    }
    
    /**
     * @test
     */
    public function returns_404_if_application_not_found()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
        ];

        $badUuid = Uuid::uuid4();

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$badUuid.'/current-step/approve', $approvalData)
            ->assertStatus(404);
    }
    
    /**
     * @test
     */
    public function validates_date_approved()
    {
        $approvalData = [
            'date_approved' => 'Carbon::now()',
        ];

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(422)
            ->assertJsonFragment(['date_approved' => ['The date approved is not a valid date.']]);
    }
    
    /**
     * @test
     */
    public function sends_no_mail_if_not_specified()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
            'notify_contacts' => 'false'
        ];

        Mail::fake();
        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200);

        Mail::assertNotSent(UserDefinedMailable::class);
    }

    /**
     * @test
     */
    public function sends_mail_to_contacts_if_specified()
    {
        $person1 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person1->uuid);

        $person2 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person2->uuid);


        $subject = 'This is a <strong>test</strong> custom message';
        $body = '<p>this is the body of a <em>custom message<em>.</p>';

        $approvalData = [
            'date_approved' => Carbon::now(),
            'notify_contacts' => true,
            'subject' => $subject,
            'body' => $body
        ];

        Mail::fake();

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200);

        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) use ($body, $subject, $person1, $person2) {
                return $mail->subject == $subject
                    && $mail->body == $body
                    && $mail->attachments == []
                    && $mail->hasTo($person1->email)
                    && $mail->hasTo($person2->email)
                ;
            }
        );
    }

    /**
     * @test
     */
    public function sends_database_notification_to_contacts_if_specified()
    {
        $person1 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person1->uuid);

        $person2 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person2->uuid);

        $approvalData = [
            'date_approved' => Carbon::now(),
            'notify_contacts' => true,
        ];

        Notification::fake();

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200);

        Notification::assertSentTo($person1, ApplicationStepApprovedNotification::class);
        Notification::assertSentTo($person2, ApplicationStepApprovedNotification::class);
    }
    

    /**
     * @test
     */
    public function test_mailable_content()
    {
        $subject = 'This is a <strong>test</strong> custom message';
        $body = '<p>this is the body of a <em>custom message<em>.</p>';

        $mailable = (new UserDefinedMailable(body: $body));
        $mailable->subject($subject);

        $view = View::make(
            'email.user_defined_email',
            [
                'body' => $body
            ]
        );

        $this->assertEquals($view, $mailable->render());
        $this->assertEquals($subject, $mailable->subject);
        $this->assertEquals([], $mailable->attachments);
    }

    /**
     * @test
     */
    public function marks_submission_approved_if_exists()
    {
        $person = Person::factory()->create();
        $submission = Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_type_id' => config('submissions.types.application.definition.id'),
            'submitter_id' => $person->id,
        ]);

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', [
            'date_approved' => Carbon::now(),
            'notify_contacts' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'submission_status_id' => config('submissions.statuses.approved.id'),
            'approved_at' => Carbon::now()
        ]);
    }

    /**
     * @test
     */
    public function records_submission_approved_if_exists()
    {
        $person = Person::factory()->create();
        $submission = Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_type_id' => config('submissions.types.application.definition.id'),
            'submitter_id' => $person->id,
        ]);

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', [
            'date_approved' => Carbon::now(),
            'notify_contacts' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'activity_type' => 'step-approved',
            'subject_id' => $this->expertPanel->group_id,
            'subject_type' => Group::class,
            'description' => 'Step 1 approved'
        ]);
    }

    /**
     * @test
     */
    public function group_status_set_to_active_when_last_step_is_approved()
    {
        $this->expertPanel->current_step = 4;
        $this->expertPanel->save();

        $person = Person::factory()->create();
        $submission = Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_type_id' => config('submissions.types.application.sustained-curation.id'),
            'submitter_id' => $person->id,
        ]);

        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', [
            'date_approved' => Carbon::now(),
            'notify_contacts' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('groups', [
            'id' => $this->expertPanel->group_id,
            'group_status_id' => config('groups.statuses.active.id')
        ]);
    }
}
