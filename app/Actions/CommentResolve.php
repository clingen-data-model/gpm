<?php

namespace App\Actions;
use App\Models\Comment;
use Carbon\Carbon;
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

        return $comment;
    }

    public function authorize(ActionRequest $request):bool
    {
        return $request->user()->hasPermissionTo('ep-applications-comment');
    }

}