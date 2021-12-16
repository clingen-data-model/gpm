<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Models\Permission;
use App\Models\Submission;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Notifications\ApplicationSubmissionNotification;

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
    public function superAdmins_receive_notification_of_approved()
    {
        $admin = $this->setupUser();
        $admin->person()->save(Person::factory()->make());
        $admin->assignRole('super-admin');
        
        Notification::fake(ApplicationSubmissionNotification::class);
        
        $this->makeRequest();

        Notification::assertSentTo($admin->person, ApplicationSubmissionNotification::class);
    }

    /**
     * @test
     */
    public function notification_store_in_database()
    {
        $admin = $this->setupUser();
        $admin->person()->save(Person::factory()->make());
        $admin->assignRole('super-admin');
        
        $this->makeRequest();

        $this->assertDatabaseHas('notifications', [
            'type' => ApplicationSubmissionNotification::class,
            'notifiable_type' => Person::class,
            'notifiable_id' => $admin->person->id
        ]);
    }
    
    
    
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
