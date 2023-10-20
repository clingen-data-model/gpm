<?php

namespace App\Actions;

use App\Events\CommentEvent;
use App\Models\Comment;
use App\Modules\Group\Actions\SubmissionNotifiablesGet;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Notifications\CommentActivityNotification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsListener;

class CommentNotifyAboutEvent
{
    use AsListener;

    public function __construct(private SubmissionNotifiablesGet $getSubmissionNotifiables)
    {
    }

    public function handle(Comment $comment, string $type): void
    {
        if ($this->commentIsAboutGroup($comment)) {
            $group = $this->getCommentGroup($comment);
            if (! $group) {
                Log::warning('Could not find group for comment', $comment->toArray);

                return;
            }
            $submission = $group->latestSubmission;

            if ($submission && $submission->isUnderChairReview) {
                $approvers = $this->getSubmissionNotifiables->handle(collect($comment->creator));

                Notification::send(
                    $approvers,
                    new CommentActivityNotification($group, $comment, $type)
                );
            }
        }
    }

    public function asListener(CommentEvent $event): void
    {
        $this->handle($event->comment, $this->getEventString($event));
    }

    private function getEventString(CommentEvent $event): string
    {
        $classParts = explode('\\', $event::class);

        return preg_replace('/Comment/i', '', $classParts[count($classParts) - 1]);
    }

    private function commentIsAboutGroup(Comment $comment): bool
    {
        if ($comment->subject_type == Group::class) {
            return true;
        }

        if ($comment->metadata['root_subject_type'] == Group::class) {
            return true;
        }

        return false;
    }

    private function getCommentGroup(Comment $comment): ?Group
    {
        if ($comment->subject_type == Group::class) {
            return $comment->subject;
        }

        if ($comment->metadata['root_subject_type'] == Group::class) {
            return Group::find($comment->metadata['root_subject_id']);
        }

        throw new Exception('Tried to get group for comment where subject is not Group and metadata.root_subject_id is not Group.');
    }
}
