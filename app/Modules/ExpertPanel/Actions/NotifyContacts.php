<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Mail\UserDefinedMailable;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Notifications\UserDefinedMailNotification;

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
        
        $mail = Mail::to($contacts->pluck('email'))
                    ->send(new UserDefinedMailable(
                        subject: $subject,
                        body: $body,
                        attachments: $attachments,
                        ccAddresses: $ccAddresses
                    ));
        // Notification::send(
        //     $contacts,
        //     new UserDefinedMailNotification(
        //         subject: $subject,
        //         body: $body,
        //         attachments: $attachments,
        //         ccAddresses: $ccAddresses
        //     )
        // );
    }
}
