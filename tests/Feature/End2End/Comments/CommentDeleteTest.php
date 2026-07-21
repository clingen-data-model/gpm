<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Submission;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Notifications\CommentActivityNotification;
use PHPUnit\Framework\Attributes\Test;

class CommentDeleteTest extends CommentTestAbstract
{
    private $comment;

    public function setup():void
    {
        parent::setup();
        Carbon::setTestNow('2022-06-28 10:00:00');
        $this->setupPermission('ep-applications-comments-manage');
        $this->comment = $this->createComment();
    }

    #[Test]
    public function user_can_delete_own_comment()
    {
        $this->makeRequest()->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'deleted_at' => Carbon::now()
        ]);
    }

    #[Test]
    public function user_with_comments_manage_permission_can_delete_any_comment()
    {
        $otherUser = $this->setupUser(null, ['comments-manage']);
        Sanctum::actingAs($otherUser);
        $this->makeRequest()->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'deleted_at' => Carbon::now()
        ]);
    }

    #[Test]
    public function user_without_special_permission_cannot_delete_another_users_comment()
    {
        $otherUser = $this->setupUser(null, ['ep-applications-comment']);
        Sanctum::actingAs($otherUser);

        $this->makeRequest()->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'deleted_at' => null
        ]);
    }

    #[Test]
    public function assertNotificationSent()
    {
        $submission = Submission::factory()->create([
            'group_id' => $this->expertPanel->group_id,
            'submission_status_id' => config('submissions.statuses.under-chair-review.id'),
            'sent_to_chairs_at' => Carbon::now()
        ]);

        $approver = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);

        Notification::fake();
        $response = $this->makeRequest();

        Notification::assertSentTo(
            $approver->person,
            CommentActivityNotification::class,
            function ($notification) {
                return true
                    && $notification->group->id === $this->expertPanel->group_id
                    && $notification->comment->id === $this->comment->id
                    && $notification->event === 'Deleted';
            }
        );
    }


    #[Test]
    public function assertNotificationNotSent()
    {
        $submission = Submission::factory()->create([
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


    private function makeRequest()
    {
        return $this->json('DELETE', '/api/comments/'.$this->comment->id);
    }


}
