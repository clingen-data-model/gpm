<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommentDeleteTest extends CommentTest
{
    public function setup():void
    {
        parent::setup();
        $this->setupPermission('ep-applications-comments-manage');
        $this->comment = $this->createComment();
    }

    /**
     * @test
     */
    public function user_can_delete_own_comment()
    {
        Carbon::setTestNow();
        $this->makeRequest()->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'deleted_at' => Carbon::now()
        ]);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
    
    
    
    private function makeRequest()
    {
        return $this->json('DELETE', '/api/comments/'.$this->comment->id);
    }
    
    
}
