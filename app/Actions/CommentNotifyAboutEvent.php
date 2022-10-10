<?php

namespace App\Actions;

use App\Models\Comment;
use App\Events\CommentEvent;
use App\Modules\User\Models\User;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\Concerns\AsListener;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\SubmissionNotifiablesGet;
use App\Modules\Group\Notifications\CommentActivityNotification;

class CommentNotifyAboutEvent
{
    use AsListener;


    public function __construct(private SubmissionNotifiablesGet $getSubmissionNotifiables)
    {
    }

    public function handle(Comment $comment, string $type): void
    {
        if ($comment->subject_type == Group::class) {

            $submission = $comment->subject->latestSubmission;

            if ($submission && $submission->isUnderChairReview) {
                $approvers = $this->getSubmissionNotifiables->handle(collect($comment->creator));

                Notification::send(
                    $approvers,
                    new CommentActivityNotification($comment->subject, $comment, $type)
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
        $classParts = explode('\\', get_class($event));
        return preg_replace('/Comment/i', '', $classParts[count($classParts)-1]);
    }
}
