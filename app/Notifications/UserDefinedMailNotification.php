<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserDefinedMailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public string $subject,
        public string $body,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?array $attachments = [],
        public ?array $ccAddresses = [],
        public ?array $bccAddresses = [],
        public ?string $replyToEmail = null,
        public ?string $replyToName = null,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->view('email.user_defined_email', [
                'body' => $this->body,
            ]);

        if ($this->fromEmail) {
            $mail->from($this->fromEmail, $this->fromName);
        }

        if ($this->replyToEmail) {
            $mail->from($this->replyToEmail, $this->replyToName);
        }

        if (count($this->ccAddresses) > 0) {
            foreach ($this->ccAddresses as $cc) {
                $mail->cc(...$cc);
            }
        }

        if (count($this->bccAddresses) > 0) {
            foreach ($this->bccAddresses as $bcc) {
                $mail->cc(...$bcc);
            }
        }

        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $attachment) {
                $mail->attach($attachment->getPath(), [
                    'as' => $attachment->getOriginalName(),
                ]);
            }
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
