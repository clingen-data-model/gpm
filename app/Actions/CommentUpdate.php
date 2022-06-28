<?php

namespace App\Actions;

use App\Models\Comment;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentUpdate
{
    	use AsController;

    public function handle(Comment $comment, array $data)
    {   
        $comment->update($data);
        return $comment;
    }

    public function asController(ActionRequest $request, Comment $comment)
    {
        return $this->handle($comment, $request->validated());
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
        return $request->user()->id == $request->comment->creator_id || $request->user()->hasPermissionTo('comments-manage');
    }

}