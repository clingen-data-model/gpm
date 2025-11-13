<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AttestationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private array $vcepNames = [])
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = rtrim(config('app.url'), '/') . '/core-approval-member-attestation';
        $subject = 'Action required: Core Approval Member Attestation';
        return (new MailMessage)->subject($subject)->view('email.attestation_reminder', ['url' => $url, 'vcepNames' => $this->vcepNames, 'person' => $notifiable]);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
