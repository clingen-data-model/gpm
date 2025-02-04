<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use App\Actions\CommentResolve;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class UnresolveCommentTest extends CommentTest
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->comment = $this->createComment();
        $act = new CommentResolve;
        $act->handle($this->comment);
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
                'resolved_at' => null
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'resolved_at' => null
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
    

    private function makeRequest($id = null): TestResponse
    {
        $id = $id ?? $this->comment->id;

        return $this->json('POST', '/api/comments/'.$id.'/unresolved');
    }
    
    
}
