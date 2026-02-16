<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CocReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $view,
        public array $viewData = []
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $att = $notifiable->latestCocAttestation ?? null;

        return (new MailMessage)
            ->subject($this->subject)
            ->view($this->view, array_merge([
                'person' => $notifiable,
                'attestation' => $att,
                'expiresAt' => $att?->expires_at,
                'attestUrl' => url('/onboarding/coc'),
                'fullLink' => config('coc.links.full'),
                'summaryLink' => config('coc.links.summary'),
            ], $this->viewData));
    }
}
