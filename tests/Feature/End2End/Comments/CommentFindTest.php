<?php

namespace Tests\Feature\End2End\Comments;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\End2End\Comments\CommentTest;

class CommentFindTest extends CommentTest
{
    public function setup():void
    {
        parent::setup();
        $this->comment = $this->createComment();
    }

    /**
     * @test
     */
    public function can_get_a_comment()
    {
        $this->json('get', '/api/comments/'.$this->comment->id)
            ->assertStatus(200)
            ->assertJson($this->comment->toArray());
    }
    
    
}
