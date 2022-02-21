<?php
namespace App\Actions;

use App\Models\Email;
use App\Mail\UserDefinedMailable;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Notifications\ValueObjects\MailAttachment;

class MailResend
{
    use AsController;

    public function __construct(private MailSendUserDefined $sendUserDefinedMail)
    {
    }

    public function handle(
        $to,
        string $subject,
        string $body,
        ?array $attachments = [],
        ?array $ccAddresses = [],
        ?array $bccAddresses = [],
    ) {
        $this->sendUserDefinedMail->handle(
            to: $to,
            subject: $subject,
            body: $body,
            attachments: $attachments,
            ccAddresses: $ccAddresses,
            bccAddresses: $bccAddresses
        );
    }

    public function asController(ActionRequest $request)
    {
        $attachments = collect($request->attachments)
                        ->map(function ($file) {
                            return MailAttachment::createFromUploadedFile($file);
                        })
                        ->toArray();

        $mailData = array_merge(
            $request->only('to', 'subject', 'body'),
            ['attachments' => $attachments, 'ccAddresses' => $request->cc, 'bccAddresses' => $request->bcc]
        );

        $this->handle(...$mailData);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('mail-log-view');
    }
}
