<?php

namespace Tests\Feature\End2End\Comments;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\End2End\Comments\CommentTest;
use PHPUnit\Framework\Attributes\Test;

class CommentFindTest extends CommentTestAbstract
{
    public function setup():void
    {
        parent::setup();
        $this->comment = $this->createComment();
    }

    #[Test]
    public function can_get_a_comment()
    {
        $this->json('get', '/api/comments/'.$this->comment->id)
            ->assertStatus(200)
            ->assertJson($this->comment->toArray());
    }


}
