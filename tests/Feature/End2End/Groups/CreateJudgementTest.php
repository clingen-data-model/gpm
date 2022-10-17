<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\Group\Notifications\JudgementActivityNotification as NotificationsJudgementActivityNotification;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Notification;
use App\Notifications\JudgementActivityNotification;

class CreateJudgementTest extends JudgementTest
{

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
                'decision' => ['This is required.']
            ]);

        $this->makeRequest(['decision' => 'hedgehogs'])
            ->assertValidationErrors([
                'decision' => ['The selection is invalid.']
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
                'decision' => 'request-revisions',
                'notes' => 'These are my comments I want to add.',
                'submission_id' => $this->submission->id,
                'person_id' => $this->user->person->id
            ]);

        $this->assertDatabaseHas('judgements', [
            'decision' => 'request-revisions',
            'notes' => 'These are my comments I want to add.',
            'submission_id' => $this->submission->id,
            'person_id' => $this->user->person->id
        ]);
    }

    /**
     * @test
     */
    public function logs_judgement_submitted_activity()
    {
        $this->makeRequest()
            ->assertStatus(201);

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: $this->user->person->name.' made a decision on the submission: request-revisions'
        );
    }

    /**
     * @test
     */
    public function notifies_other_notifiables_when_judgement_created()
    {
        $otherApprover = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        $commenter = $this->setupUserWithPerson(permissions: ['ep-applications-comment']);

        // Not using Notification:fake b/c it's not registering notifications being sent,
        // but they're getting added the database without that. 🤷‍♀️
        $this->makeRequest()
            ->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $otherApprover->person->id,
            'type' => NotificationsJudgementActivityNotification::class
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $commenter->person->id,
            'type' => NotificationsJudgementActivityNotification::class
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->user->person->id,
            'type' => NotificationsJudgementActivityNotification::class
        ]);
    }



    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [
            'decision' => 'request-revisions',
            'notes' => "These are my comments I want to add.",
            'person_id' => $this->user->person->id
        ];

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/application/judgements', $data);
    }

}
