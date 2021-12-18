<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use App\Models\Submission;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\View;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Notifications\UserDefinedMailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApproveStepTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
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
    public function sends_no_notifications_if_not_specified()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
            'notify_contacts' => 'false'
        ];

        Notification::fake();
        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200);

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function sends_notification_to_contacts_if_specified()
    {
        $person = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person->uuid);


        $subject = 'This is a <strong>test</strong> custom message';
        $body = '<p>this is the body of a <em>custom message<em>.</p>';

        $approvalData = [
            'date_approved' => Carbon::now(),
            'notify_contacts' => true,
            'subject' => $subject,
            'body' => $body
        ];

        Notification::fake();
        Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200);

        Notification::assertSentTo(
            $person,
            UserDefinedMailNotification::class,
            function ($notification) use ($body, $subject) {
                return $notification->subject == $subject
                    && $notification->body == $body
                    && $notification->attachments == []
                ;
            }
        );

        $mailable = (new UserDefinedMailNotification(subject: $subject, body: $body))->toMail($person);

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
            'approved_at' => Carbon::now(),
        ]);
    }
}
