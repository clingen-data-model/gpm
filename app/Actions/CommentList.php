<?php

namespace App\Actions;

use App\Models\Comment;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentList
{
    use AsController;

    public function handle($queryParams)
    {
        // Fixed scope and eager loads: never pass client relation/deleted filters through.
        $where = $queryParams['where'] ?? [];
        abort_unless(is_string($where['subject_type'] ?? null) && is_numeric($where['subject_id'] ?? null), 422);
        app(\App\Services\ApplicationReviewCommentAccess::class)->authorizeSubject(
            auth()->user(), $where['subject_type'], (int) $where['subject_id'], $queryParams['group_id'] ?? null
        );
        return Comment::where('subject_type', $where['subject_type'])
            ->where('subject_id', $where['subject_id'])
            ->with(['type', 'creator' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'email')])
            ->withCount('comments')->get();
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle($request->all());
    }
}