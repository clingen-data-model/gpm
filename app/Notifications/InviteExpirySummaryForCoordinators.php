<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InviteExpirySummaryForCoordinators extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $groupId,
        public readonly string $groupName,
        /** @var array<int, array{person_id:int,name:string,email:string,invite_created_at:?string}> */
        public readonly array $removedRows,
        public readonly string $expiredAt,
        public readonly int $ttlDays
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $count = count($this->removedRows);

        $mail = (new MailMessage)
            ->subject("Expired GPM invites — {$this->groupName}")
            ->greeting("Hello Coordinator,")
            ->line("This notification is to inform you that the following individuals within your {$this->groupName} have been removed from membership due to an expiration of their GPM invite.")
            ->line('All members have **30 days** to redeem invites before they expire. If the individual(s) are still important for group membership, you can add them back as a member and the 30 day expiration cycle starts again.')
            ->line('If you have any questions or concerns, please email gpm_support@clinicalgenome.org')
            ->line('')
            ->line('Expired Individuals:');

        foreach ($this->removedRows as $row) {
            $mail->line("- {$row['name']} <{$row['email']}>");
        }

        $mail->salutation('— GPM / Clinical Genome Team');

        return $mail;
    }
}
