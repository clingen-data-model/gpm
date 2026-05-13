<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use App\Actions\CommentResolve;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class UnresolveCommentTest extends CommentTestAbstract
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->comment = $this->createComment();
        $act = new CommentResolve;
        $act->handle($this->comment);
    }

    #[Test]
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

    #[Test]
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
