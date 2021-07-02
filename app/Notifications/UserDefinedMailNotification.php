<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
    public function __construct(private String $subject, private String $body, private ?String $fromEmail = null, private array $attachments = [])
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
                    ->subject($this->subject)
                    ->view('email.user_defined_email', [
                        'body' => $this->body
                    ]);

        if ($this->fromEmail) {
            $mail->from($this->fromEmail);
        }

        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $attachment) {
                $mail->attach($attachment->getPath(), [
                    'as' => $attachment->getOriginalName(),
                ]);
            }
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
