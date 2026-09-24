<?php

namespace App\Actions;

use App\Events\CommentDeleted;
use App\Models\Comment;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentDelete
{
    	use AsController;

    public function handle(Comment $comment)
    {
        $cmt = $comment;
        $comment->delete();
        event(new CommentDeleted($cmt));
    }

    public function asController(ActionRequest $request, Comment $comment)
    {
        return $this->handle($comment);
    }

    public function authorize(ActionRequest $request, Comment $comment):bool
    {
        return ($request->user()->person
                && $comment->creator_type === $request->user()->person->getMorphClass()
                && $comment->creator_id === $request->user()->person->id)
            || $request->user()->hasPermissionTo('comments-manage');
    }

}
