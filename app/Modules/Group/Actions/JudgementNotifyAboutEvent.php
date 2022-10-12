<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Judgement;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Modules\Group\Events\JudgementEvent;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Actions\SubmissionNotifiablesGet;
use App\Modules\Group\Notifications\JudgementActivityNotification;

class JudgementNotifyAboutEvent
{
    use AsListener;

    public function __construct(private SubmissionNotifiablesGet $getSubmissionNotifiables)
    {
    }


    public function handle(Judgement $judgement, string $type)
    {
        $approvers = $this->getSubmissionNotifiables->handle(collect($judgement->person));

        Notification::send(
            $approvers,
            new JudgementActivityNotification($judgement->submission->group, $judgement, $type)
        );
    }

    public function asListener(JudgementEvent $event): void
    {
        $this->handle($event->judgement, $this->getEventString($event));
    }

    private function getEventString(JudgementEvent $event): string
    {
        $classParts = explode('\\', get_class($event));
        return preg_replace('/Comment/i', '', $classParts[count($classParts)-1]);
    }


}
