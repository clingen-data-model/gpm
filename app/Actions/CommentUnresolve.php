<?php

namespace App\Actions;

use App\Models\Comment;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentUnresolve
{
    	use AsController;

    public function handle(Comment $comment)
    {
        $comment->resolved_at = null;
        $comment->save();

        return $comment;
    }

    public function authorize(ActionRequest $request):bool
    {
        return $request->user()->hasPermissionTo('ep-applications-comment');
    }

}