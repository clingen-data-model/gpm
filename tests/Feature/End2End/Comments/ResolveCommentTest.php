<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use App\Events\CommentCreated;
use App\Actions\CommentResolve;
use App\Events\CommentResolved;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Notifications\CommentActivityNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class ResolveCommentTest extends CommentTest
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->comment = $this->createComment();
    }

    /**
     * @test
     */
    public function permissioned_user_can_resolve_comment()
    {
        Carbon::setTestNow('2022-07-11');
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->comment->id,
                'resolved_at' => Carbon::now()->toIsoString()
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'resolved_at' => Carbon::now()
        ]);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_resolve_comment()
    {
        $this->user->revokePermissionTo('ep-applications-comment');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function does_not_overwrite_not_null_resolved_at()
    {
        Carbon::setTestNow('2021-07-11');
        $originalDate = Carbon::now();
        (new CommentResolve)->handle($this->comment);

        Carbon::setTestNow('2022-07-11');
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->comment->id,
                'resolved_at' => $originalDate->toISOString()
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'resolved_at' => $originalDate
        ]);
    }

    /**
     * @test
     */
    public function resolve_assertNotificationSent()
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
                    && $notification->event = 'resolved'
                ;
            }
        );
    }


    /**
     * @test
     */
    public function resolve_assertNotificationNotSent()
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


    private function makeRequest($id = null): TestResponse
    {
        $id = $id ?? $this->comment->id;

        return $this->json('POST', '/api/comments/'.$id.'/resolved');
    }


}
