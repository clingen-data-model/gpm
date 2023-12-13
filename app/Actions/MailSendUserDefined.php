<?php
namespace App\Actions;

use InvalidArgumentException;
use App\Mail\UserDefinedMailable;
use Illuminate\Support\Facades\Mail;
use App\Notifications\ValueObjects\MailAttachment;

class MailSendUserDefined
{
    /**
     * Sends a UserDefinedMailable
     * 
     * @param string $subject Subject of email
     * @param string $body Body content of email
     * @param MailAttachment[] $attachments attachments to be sent with email
     * @param array[] $ccAddresses array of arrays with structure ['name' => 'name', 'address' => 'address@example.com']
     * @param array[] $bccAddresses array of arrays with structure ['name' => 'name', 'address' => 'address@example.com']
     * @param array $from array with structure ['name' => 'name', 'address' => 'address@example.com']
     */
    public function handle(
        $to,
        string $subject,
        string $body,
        ?array $attachments = null,
        ?array $ccAddresses = null,
        ?array $bccAddresses = null,
    ) {
        $ccAddresses = $ccAddresses ?? [];
        $bccAddresses = $bccAddresses ?? [];
        $attachements = $attachements ?? [];

        $mailable = new UserDefinedMailable(body: $body);
        $mailable->subject($subject);

        if (count($ccAddresses) > 0) {
            foreach ($ccAddresses as $cc) {
                $mailable->cc(...$cc);
            }
        }

        if (count($bccAddresses) > 0) {
            foreach ($bccAddresses as $bcc) {
                $mailable->bcc(...$bcc);
            }
        }

        if (count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                $mailable->attach($attachment->getPath(), [
                    'as' => $attachment->getOriginalName(),
                ]);
            }
        }

        $mailable = $this->setRecipients($to, $mailable);

        Mail::send($mailable);
    }

    private function setRecipients($to, $mailable)
    {
        if (is_string($to)) {
            return $mailable->to($to);
        }

        if (count($to) == 0) {
            throw new InvalidArgumentException('Expected at least 1 to address, none given.');
        }

        foreach ($to as $recipient) {
            if (is_string($recipient)) {
                $mailable->to($recipient);
                continue;
            }
            $mailable->to(...$recipient);
        }
        return $mailable;
    }
}
