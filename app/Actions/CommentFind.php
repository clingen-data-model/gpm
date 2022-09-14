<?php

namespace App\Actions;
use App\Models\Comment;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentFind
{
    	use AsController;

    public function handle(Comment $comment)
    {
        return $comment;
    }
}