<?php

namespace App\Modules\Group\Actions;

use App\Models\Submission;
use App\Modules\User\Models\User;
use Lorisleiva\Actions\Concerns\AsListener;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Notifications\ApplicationSubmissionNotification;

class ApplicationSubmissionNotificationSend
{
    use AsListener;

    public function handle(Submission $submission)
    {
        $superAdmins = User::role('super-admin')->get()->pluck('person');

        Notification::send($superAdmins, new ApplicationSubmissionNotification($submission));
    }

    public function asListener(ApplicationStepSubmitted $event)
    {
        $this->handle($event->submission);
    }
}
