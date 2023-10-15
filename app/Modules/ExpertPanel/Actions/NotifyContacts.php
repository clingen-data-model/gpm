<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Actions\MailSendUserDefined;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Lorisleiva\Actions\Concerns\AsAction;

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
        array $ccAddresses
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
    }
}
