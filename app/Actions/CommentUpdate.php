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
        if (array_key_exists('metadata', $data)) {
            $metadata = $data['metadata'] ?? [];
            unset($metadata['review_submission_id'], $metadata['review_step']);
            $data['metadata'] = array_merge($metadata, \Illuminate\Support\Arr::only(
                $comment->metadata ?? [], ['review_submission_id', 'review_step']));
        }
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
        return ($request->user()->person
                && $comment->creator_type === $request->user()->person->getMorphClass()
                && $comment->creator_id === $request->user()->person->id)
            || $request->user()->hasPermissionTo('comments-manage');
    }

}
