<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use App\Mail\ApplicationStepSubmittedReceiptMail;
use App\Mail\ApplicationSubmissionAdminMail;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Permission;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use Database\Seeders\TaskTypeSeeder;
use Illuminate\Support\Facades\Mail;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Notifications\ApplicationSubmissionNotification;
use Tests\Feature\End2End\Groups\Members\SetsUpGroupPersonAndMember;

/**
 * @group submissions
 * @group applications
 */
class SubmitApplicationStepTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;
    
    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(SubmissionTypeAndStatusSeeder::class);
        $this->setupRoles(['super-admin']);
        $this->setupPermission(['application-edit']);

        $this->user = $this->setupUserWithPerson(null, ['ep-applications-manage']);
        $this->expertPanel = $this->setupVcep();
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_submit_application()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function a_privileged_user_can_submit_appliation()
    {
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'group_id' => $this->expertPanel->group->id,
                'submission_status_id' => config('submissions.statuses.pending.id'),
                'submission_type_id' => config('submissions.types.application.definition.id'),
                'status' => config('submissions.statuses.pending'),
                'type' => config('submissions.types.application.definition'),
                'notes' => 'Notes from the submitter!',
                'submitter_id' => $this->user->person->id
            ]);
        
        $this->user->revokePermissionTo('ep-applications-manage');
        $person = $this->user->person()->save(Person::factory()->make());
        $membership = $this->user->person->memberships()->save(GroupMember::factory()->make([
            'group_id' => $this->expertPanel->group->id
        ]));
        $membership->givePermissionTo('application-edit');

        Sanctum::actingAs($this->user);
        $this->makeRequest()
            ->assertStatus(201);

        $this->assertDatabaseHas('submissions', [
            'group_id' => $this->expertPanel->group->id,
            'submission_status_id' => config('submissions.statuses.pending.id'),
            'submission_type_id' => config('submissions.types.application.definition.id'),
            'notes' => 'Notes from the submitter!',
            'submitter_id' => $this->user->person->id,
        ]);
    }

    /**
     * @test
     */
    public function automatically_sets_type_to_match_current_step()
    {
        $this->expertPanel->current_step = 4;
        $this->expertPanel->save();
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'group_id' => $this->expertPanel->group->id,
                'status' => config('submissions.statuses.pending'),
                'type' => config('submissions.types.application.sustained-curation'),
                'notes' => 'Notes from the submitter!'
            ]);
    }

    /**
     * @test
     */
    public function sets_step_1_date_received_set_when_definition_submitted()
    {
        Carbon::setTestNow('2022-01-01');
        $this->expertPanel->current_step = 1;
        $this->expertPanel->save();
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'group_id' => $this->expertPanel->group->id,
                'status' => config('submissions.statuses.pending'),
                'type' => config('submissions.types.application.definition'),
                'notes' => 'Notes from the submitter!'
            ]);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'step_1_received_date' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * @test
     */
    public function sets_step_4_date_received_set_when_sustainedcuration_submitted()
    {
        Carbon::setTestNow('2022-01-01');
        $this->expertPanel->current_step = 4;
        $this->expertPanel->save();
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'group_id' => $this->expertPanel->group->id,
                'status' => config('submissions.statuses.pending'),
                'type' => config('submissions.types.application.sustained-curation'),
                'notes' => 'Notes from the submitter!'
            ]);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'step_4_received_date' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
    

    /**
     * @test
     */
    public function fires_ApplicationStepSubmitted_event()
    {
        Event::fake(ApplicationStepSubmitted::class);
        $this->makeRequest()
            ->assertStatus(201);

        Event::assertDispatched(ApplicationStepSubmitted::class);
    }
    
    /**
     * @test
     */
    public function records_ApplicationStepSubmitted_activity()
    {
        $this->makeRequest()
            ->assertStatus(201);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Group::class,
            'subject_id' => $this->expertPanel->group_id,
            'activity_type' => 'application-step-submitted'
        ]);
    }
    

    /**
     * @test
     */
    public function cdwg_oc_receives_mail_of_submitted_vcep()
    {
        Mail::fake();
        
        $this->makeRequest();

        Mail::assertSent(ApplicationSubmissionAdminMail::class, function ($mailable) {
            $mailable->build();
            return $mailable->to == [['name'=> 'CDWG Oversight Committee', 'address' => 'cdwg_oversightcommittee@clinicalgenome.org']]
                && $mailable->submission->id == $this->expertPanel->group->latestPendingSubmission->id;
        });
    }


    /**
     * @test
     */
    public function gcwg_receives_mail_of_submitted_vcep()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.gcep.id')]);

        Mail::fake();
        
        $this->makeRequest();

        Mail::assertSent(ApplicationSubmissionAdminMail::class, function ($mailable) {
            $mailable->build();
            return $mailable->to == [['name'=> 'Gene Curation Working Group', 'address' => 'genecuration@clinicalgenome.org']]
                && $mailable->submission->id == $this->expertPanel->group->latestPendingSubmission->id;
        });
    }

    /**
     * @test
     */
    public function contacts_receive_receipt_of_submission_email()
    {
        $person = Person::factory()->create();
        $this->setupMember($this->expertPanel->group, $person, ['is_contact' => true]);

        Mail::fake();
        
        $this->makeRequest();

        Mail::assertSent(ApplicationStepSubmittedReceiptMail::class, function ($mailable) {
            $mailable->build();
            return $mailable->to == [['name'=> $this->groupMember->person->name, 'address' => $this->groupMember->person->email]]
                && $mailable->expertPanel->id == $this->expertPanel->id;
        });
    }
    

    /**
     * @test
     */
    // public function notification_store_in_database()
    // {
    //     $admin = $this->setupUser();
    //     $admin->person()->save(Person::factory()->make());
    //     $admin->assignRole('super-admin');
        
    //     $this->makeRequest();

    //     $this->assertDatabaseHas('notifications', [
    //         'type' => ApplicationSubmissionNotification::class,
    //         'notifiable_type' => Person::class,
    //         'notifiable_id' => $admin->person->id
    //     ]);
    // }
    
    
    
    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'notes' => 'Notes from the submitter!'
        ];

        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/application/submission';
        return $this->json('post', $url, $data);
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
