<?php

namespace Tests\Feature\End2End\Comments;

use Tests\Feature\End2End\Comments\CommentTest;
use PHPUnit\Framework\Attributes\Test;

class CommentTypeListTest extends CommentTestAbstract
{
    #[Test]
    public function retrieves_all_comment_types()
    {
        $this->json('get', '/api/comment-types')
            ->assertStatus(200)
            ->assertJsonCount(count(config('comments.types')));
    }

}
