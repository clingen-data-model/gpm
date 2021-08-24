<?php

namespace App\Modules\ExpertPanel\Actions;

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

        Notification::send(
            $contacts,
            new UserDefinedMailNotification(
                subject: $subject,
                body: $body,
                attachments: $attachments,
                ccAddresses: $ccAddresses
            )
        );
    }
}
