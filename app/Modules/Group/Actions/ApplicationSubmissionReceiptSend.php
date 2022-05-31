<?php

namespace App\Modules\Group\Actions;

use App\Mail\ApplicationStepSubmittedReceiptMail;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsListener;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Actions\NotifyContacts;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Notifications\ApplicationSubmissionAdminNotification;

/**
 * Action that sends notification to admins when an application step has been submitted.
 */
class ApplicationSubmissionReceiptSend
{
    use AsListener;

    public function __construct(private ContactsMail $mailContacts)
    {
    }
    

    public function handle(Submission $submission)
    {
        $this->mailContacts->handle(
            $submission->group, 
            new ApplicationStepSubmittedReceiptMail($submission->group->expertPanel)
        );
    }

    public function asListener(ApplicationStepSubmitted $event)
    {
        $this->handle($event->submission);
    }
}
