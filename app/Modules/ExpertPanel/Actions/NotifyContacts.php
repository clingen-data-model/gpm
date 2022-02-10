<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Mail\UserDefinedMailable;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Notifications\UserDefinedMailNotification;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;

class NotifyContacts
{
    use AsAction;
    
    public function handle(
        ExpertPanel $expertPanel,
        string $subject,
        string $body,
        array $attachments,
        array $ccAddresses
    ) {
        $contacts = $expertPanel
                        ->contacts()
                        ->with('person')
                        ->get()
                        ->pluck('person');
        
        $mailable = new UserDefinedMailable(
            // subject: $subject,
            body: $body,
        );

        $mailable->subject($subject);

        if (count($ccAddresses) > 0) {
            foreach ($ccAddresses as $cc) {
                $mailable->cc(...$cc);
            }
        }

        if (count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                $mailable->attach($attachment->getPath(), [
                    'as' => $attachment->getOriginalName(),
                ]);
            }
        }


        $mail = Mail::to($contacts->pluck('email'))
                    ->send($mailable);
    }
}
