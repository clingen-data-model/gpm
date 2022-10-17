<?php

namespace App\Actions;
use Carbon\Carbon;
use App\Models\Comment;
use App\Events\CommentResolved;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentResolve
{
    use AsController;

    public function handle(Comment $comment)
    {
        if ($comment->isResolved) {
            return $comment;
        }

        $comment->resolved_at = Carbon::now();
        $comment->save();

        event(new CommentResolved($comment));

        return $comment;
    }

    public function authorize(ActionRequest $request):bool
    {
        return $request->user()->hasPermissionTo('ep-applications-comment');
    }

}
