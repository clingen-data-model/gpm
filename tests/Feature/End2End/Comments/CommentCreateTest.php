<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use Database\Seeders\CommentTypesSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use App\Modules\Group\Notifications\CommentActivityNotification;

class CommentCreateTest extends CommentTest
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_a_comment()
    {
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJson($expectedData);
        $this->assertDatabaseHas('comments', [
            'subject_id' => $expectedData['subject_id'],
            'subject_type' => $expectedData['subject_type'],
            'content' => $expectedData['content'],
        ]);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_create_a_comment()
    {
        $this->user->revokePermissionTo('ep-applications-comment');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_input()
    {
        $this->makeRequest([])
            ->assertValidationErrors([
                'content' => 'This is required.',
                'subject_type' => 'This is required.',
                'subject_id' => 'This is required.',
                'comment_type_id' => 'This is required.',
            ]);

        $this->makeRequest(['comment_type_id' => 999, 'metadata' => 'bob'])
            ->assertValidationErrors([
                'comment_type_id' => 'The selection is invalid.',
                'metadata' => 'This must be an array.'
            ]);
    }

    /**
     * @test
     */
    public function comment_created_assertNotificationSent()
    {
        $submission = Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_status_id' => config('submissions.statuses.under-chair-review.id'),
            'sent_to_chairs_at' => Carbon::now()
        ]);

        $approver = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);

        Notification::fake();
        $response = $this->makeRequest();

        $comment = $response->original;

        Notification::assertSentTo(
            $approver->person,
            CommentActivityNotification::class,
            function ($notification) use ($comment) {
                return true
                    && $notification->group->id = $this->expertPanel->group_id
                    && $notification->comment->id = $comment->id
                    && $notification->event = 'created'
                    ;
            }
        );
    }

    /**
     * @test
     */
    public function notification_sent_if_subject_is_reply_to_group_comment()
    {
        Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_status_id' => config('submissions.statuses.under-chair-review.id'),
        ]);

        $originalComment = Comment::factory()->create(['subject_type' => Group::class, 'subject_id' => $this->expertPanel->group_id]);

        $approver = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);

        Notification::fake();
        $response = $this->makeRequest($this->getDefaultData([
            'subject_type' => Comment::class,
            'subject_id' => $originalComment->id,
            'metadata' => [
                'section' => 'basic-info',
                'root_subject_type' => Group::class,
                'root_subject_id' => $this->expertPanel->group_id
            ]
        ]));

        $comment = $response->original;

        Notification::assertSentTo(
            $approver->person,
            CommentActivityNotification::class,
            function ($notification) use ($comment) {
                return true
                    && $notification->group->id = $this->expertPanel->group_id
                    && $notification->comment->id = $comment->id
                    && $notification->event = 'created'
                    ;
            }
        );
    }

    /**
     * @test
     */
    public function notification_not_sent_if_submission_not_sent_to_chairs()
    {
        Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_status_id' => config('submissions.statuses.pending.id'),
        ]);

        $approver = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);

        Notification::fake();
        $response = $this->makeRequest();

        Notification::assertNotSentTo(
            $approver->person,
            CommentActivityNotification::class
        );
    }

    /**
     * @test
     */
    public function notification_not_sent_to_comment_creator()
    {
        $submission = Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_status_id' => config('submissions.statuses.under-chair-review.id'),
            'sent_to_chairs_at' => Carbon::now()
        ]);

        $approver = $this->setupUserWithPerson(permissions: ['ep-applications-approve', 'ep-applications-comment']);
        Sanctum::actingAs($approver);

        $otherApprover = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);

        Notification::fake();
        $response = $this->makeRequest();

        $comment = $response->original;

        Notification::assertSentTo(
            $otherApprover->person,
            CommentActivityNotification::class
        );
        Notification::assertNotSentTo($approver->person, CommentActivityNotification::class);
    }


    private function makeRequest($data = null)
    {
        $data = $data ?? $this->getDefaultData();
        return $this->json('POST', '/api/comments', $data);
    }

    private function getDefaultData($data = [])
    {
        return array_merge([
            'comment_type_id' => config('comments.types.suggestion.id'),
            'subject_id' => $this->expertPanel->group->id,
            'subject_type' => get_class($this->expertPanel->group),
            'content' => 'This is just a suggestion.',
            'metadata' => ['section' => 'basic-info']
        ], $data);
    }





}
