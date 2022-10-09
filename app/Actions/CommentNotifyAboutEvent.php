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
use App\Modules\Group\Notifications\CommentActivityNotification;

class CommentNotifyAboutEvent
{
    use AsListener;

    public function handle(Comment $comment, string $type): void
    {
        if ($comment->subject_type == Group::class) {

            $approvers = $this->getApprovers($comment);

            Notification::send(
                $approvers,
                new CommentActivityNotification($comment->subject, $comment, $type)
            );
        }
    }

    public function asListener(CommentEvent $event): void
    {
        $this->handle($event->comment, $this->getEventString($event));
    }


    private function getApprovers(Comment $comment): Collection
    {
        $submission = $comment->subject->latestSubmission;
        if ($submission && $submission->isUnderChairReview) {
            return User::permission(['ep-applications-approve', 'ep-applications-comment'])
                    ->with('person')
                    ->whereDoesntHave('person', function($q) use ($comment) {
                        $q->where('id', $comment->creator->id);
                    })
                    ->get()
                    ->pluck('person');
        }

        return collect();
    }

    private function getEventString(CommentEvent $event): string
    {
        $classParts = explode('\\', get_class($event));
        return preg_replace('/Comment/i', '', $classParts[count($classParts)-1]);
    }



}
