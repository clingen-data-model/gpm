<?php

namespace App\Services;

use App\Models\Comment;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use Illuminate\Support\Collection;

class ApplicationReviewCommentContext
{
    public function submissions(Group $group): Collection
    {
        return Submission::withTrashed()->where('group_id', $group->id)
            ->whereIn('submission_type_id', [config('submissions.types.application.definition.id'),
                config('submissions.types.application.sustained-curation.id')])
            ->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    public function step(Submission $submission): int
    {
        return (int) $submission->submission_type_id === (int) config('submissions.types.application.sustained-curation.id')
            || (int) data_get($submission->data, 'approval_step') === 4
            || (int) data_get($submission->data, 'step') === 4 ? 4 : 1;
    }

    public function forComment(Comment $comment, Collection $submissions): array
    {
        $metadata = $comment->metadata ?? [];
        if (array_key_exists('review_submission_id', $metadata)) {
            $submission = $submissions->firstWhere('id', $metadata['review_submission_id']);
            return ['review_submission_id' => $submission?->id,
                'review_step' => $submission ? $this->step($submission) : ($metadata['review_step'] ?? 1)];
        }
        // Legacy comments have no round link. Use the submission timeline, never the live workflow step.
        // Comments predating all submissions belong to the initial application review.
        $submission = $submissions->first(fn ($item) => $item->created_at <= ($comment->created_at ?? now()));
        return ['review_submission_id' => $submission?->id, 'review_step' => $submission ? $this->step($submission) : 1];
    }

    public function capture(array $data): array
    {
        if (($data['subject_type'] ?? null) !== (new Group)->getMorphClass()) return $data;
        $group = Group::find($data['subject_id']);
        if (!$group) return $data;
        $comment = new Comment($data);
        $metadata = $comment->metadata ?? [];
        unset($metadata['review_submission_id'], $metadata['review_step']);
        $comment->metadata = $metadata;
        $data['metadata'] = $metadata + $this->forComment($comment, $this->submissions($group));
        return $data;
    }
}
