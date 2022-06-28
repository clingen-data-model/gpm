<?php

namespace Tests\Feature\End2End\comments;

use Tests\Feature\End2End\Comments\CommentTest;

class CommentTypeListTest extends CommentTest
{
    /**
     * @test
     */
    public function retrieves_all_comment_types()
    {
        $this->json('get', '/api/comment-types')
            ->assertStatus(200)
            ->assertJsonCount(count(config('comments.types')));
    }
    
}
