<?php

namespace App\Services;

use App\Models\Comment;
use App\Modules\Group\Models\Group;
use App\Modules\User\Models\User;

class ApplicationReviewCommentAccess
{
    public function allowed(User $user): bool
    {
        return $user->can('ep-applications-manage')
            || $user->can('ep-applications-comment')
            || $user->can('ep-applications-approve');
    }

    public function authorize(User $user, Group $group): void
    {
        abort_unless($this->allowed($user) && $user->can('view', $group), 403);
    }

    public function groupForSubject(string $type, int $id): Group
    {
        $visited = [];
        while ($type === (new Comment)->getMorphClass()) {
            abort_if(isset($visited[$id]), 404);
            $visited[$id] = true;
            $comment = Comment::findOrFail($id);
            $type = $comment->subject_type;
            $id = $comment->subject_id;
        }
        abort_unless($type === (new Group)->getMorphClass(), 404);
        return Group::findOrFail($id);
    }

    public function authorizeSubject(User $user, string $type, int $id, $requestedGroupId = null): void
    {
        $group = $this->groupForSubject($type, $id);
        abort_if($requestedGroupId !== null && (string) $group->id !== (string) $requestedGroupId, 403);
        $this->authorize($user, $group);
    }
}
