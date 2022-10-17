<?php

namespace App\Actions;

use App\Events\CommentUpdated;
use App\Models\Comment;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentUpdate
{
    use AsController;

    public function handle(Comment $comment, array $data)
    {
        $comment->update($data);

        event(new CommentUpdated($comment));

        return $comment;
    }

    public function asController(ActionRequest $request, Comment $comment)
    {
        $comment = $this->handle($comment, $request->validated());

        $comment->load(['creator', 'type']);

        return $comment;
    }

    public function rules(ActionRequest $request): array
    {
        return [
            'content' => 'required',
            'comment_type_id' => 'required|exists:comment_types,id',
            'metadata' => 'nullable|array',
        ];
    }

    public function authorize(ActionRequest $request, Comment $comment):bool
    {
        return $request->user()->id == $request->comment->creator_id
            || $request->user()->hasPermissionTo('comments-manage');
    }

}
