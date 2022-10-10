<?php

namespace Tests\Feature\End2End\Groups;

use App\Notifications\JudgementActivityNotification;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Notification;

class JudgementCreateTest extends JudgementTest
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

        Notification::fake();
        $this->makeRequest()
            ->assertStatus(201);

        Notification::assertNotSentTo($this->user, JudgementActivityNotification::class);
        Notification::assertSentTo($otherApprover, JudgementActivityNotification::class);
        Notification::assertSentTo($commenter, JudgementActivityNotification::class);
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
