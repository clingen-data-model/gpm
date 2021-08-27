<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDefinedMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        public String $subject,
        public String $body,
        public ?String $fromEmail = null,
        public ?String $fromName = null,
        public ?array $attachments = [],
        public ?array $ccAddresses = [],
        public ?array $bccAddresses = [],
        public ?string $replyToEmail = null,
        public ?string $replyToName = null,
    ) {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->fromEmail) {
            $this->from($this->fromEmail, $this->fromName);
        }

        if ($this->replyToEmail) {
            $this->from($this->replyToEmail, $this->replyToName);
        }

        if (count($this->ccAddresses) > 0) {
            foreach ($this->ccAddresses as $cc) {
                $this->cc(...$cc);
            }
        }

        if (count($this->bccAddresses) > 0) {
            foreach ($this->bccAddresses as $bcc) {
                $this->cc(...$cc);
            }
        }

        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $attachment) {
                $this->attach($attachment->getPath(), [
                    'as' => $attachment->getOriginalName(),
                ]);
            }
        }
        return view('email.user_defined_email')
            ->with([
                'body' => $this->body
            ]);
    }
}
