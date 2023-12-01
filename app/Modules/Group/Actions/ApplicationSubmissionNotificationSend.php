<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Notifications\ApplicationSubmissionNotification;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsListener;

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
