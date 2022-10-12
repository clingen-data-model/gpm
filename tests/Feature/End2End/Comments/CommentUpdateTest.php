<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Submission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\End2End\Comments\CommentTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Notifications\CommentActivityNotification;

class CommentUpdateTest extends CommentTest
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->comment = $this->createComment();
    }

    /**
     * @test
     */
    public function creator_can_update_own_comment()
    {
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson($expectedData)
            ;

        $this->assertDatabaseHas('comments', $this->jsonifyArrays($expectedData));
    }

    /**
     * @test
     */
    public function user_with_comments_manage_perm_can_update_others_comment()
    {
        $otherUser = $this->setupUser(null, ['comments-manage']);
        Sanctum::actingAs($otherUser);
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson($expectedData)
            ;

        $this->assertDatabaseHas('comments', $this->jsonifyArrays($expectedData));
    }

    /**
     * @test
     */
    public function user_cannot_update_others_comment()
    {
        $otherUser = $this->setupUser();
        Sanctum::actingAs($otherUser);
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(403);

        $this->assertDatabaseMissing('comments', $this->jsonifyArrays($expectedData));
    }

    /**
     * @test
     */
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

        $comment = $response->original;

        Notification::assertSentTo(
            $approver->person,
            CommentActivityNotification::class,
            function ($notification) use ($comment) {
                return true
                    && $notification->group->id = $this->expertPanel->group_id
                    && $notification->comment->id = $comment->id
                    && $notification->event = 'updated';
            }
        );
    }


    /**
     * @test
     */
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

    private function makeRequest($data = null)
    {
        $data = $data ?? $this->getDefaultData();
        return $this->json('PUT', '/api/comments/'.$this->comment->id, $data);
    }

    private function getDefaultData($data = [])
    {
        return array_merge([
            'content' => 'updated content',
            'comment_type_id' => config('comments.types.required-revision.id')
        ], $data);
    }




}
