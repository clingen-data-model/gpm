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
        app(\App\Services\ApplicationReviewCommentAccess::class)->authorizeSubject(
            auth()->user(), $comment->subject_type, $comment->subject_id, request('group_id')
        );
        return $comment;
    }
}