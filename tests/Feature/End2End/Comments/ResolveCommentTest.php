<?php

namespace Tests\Feature\End2End\Comments;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use App\Actions\CommentResolve;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResolveCommentTest extends CommentTest
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
    
    

    private function makeRequest($id = null): TestResponse
    {
        $id = $id ?? $this->comment->id;

        return $this->json('POST', '/api/comments/'.$id.'/resolved');
    }
    
    
}
