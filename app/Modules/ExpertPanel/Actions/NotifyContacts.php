<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Actions\MailSendUserDefined;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class NotifyContacts
{
    use AsAction;
    
    public function __construct(private MailSendUserDefined $sendUserDefinedMail)
    {
    }
    

    public function handle(
        ExpertPanel $expertPanel,
        string $subject,
        string $body,
        array $attachments,
        array $ccAddresses,
        ?int $step = null,
        bool $recordEmail = true,
    ) {
        $contacts = $expertPanel
                        ->contacts()
                        ->with('person')
                        ->get()
                        ->pluck('person');
        

        $this->sendUserDefinedMail->handle(
            subject: $subject,
            body: $body,
            attachments: $attachments,
            ccAddresses: $ccAddresses,
            to: $contacts->pluck('email')
        );
        if ($recordEmail) {
            event(new \App\Modules\Group\Events\ApplicationEmailSent(
                $expertPanel->group, $subject, $body, $step ?? $expertPanel->current_step
            ));
        }
    }
}
