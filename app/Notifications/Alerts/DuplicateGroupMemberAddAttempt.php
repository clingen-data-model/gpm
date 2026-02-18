<?php

namespace App\Notifications\Alerts;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Channels\SlackWebhookChannel;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Models\User;

class DuplicateGroupMemberAddAttempt extends Notification
{
    use Queueable;

    public function __construct(
        public Group $group,
        public Person $person,
        public ?User $actor = null,
        public array $payload = []
    ) {}

    public function via($notifiable): array
    {
        return [SlackWebhookChannel::class];
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->from(config('app.name').' Alert', ':warning:')
            ->content('Duplicate group member add attempt blocked.')
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'Group ID'   => (string) $this->group->id,
                    'Person ID'  => (string) $this->person->id,
                    'Actor ID'   => $this->actor?->id ? (string) $this->actor->id : '(none)',
                    'Payload'    => json_encode($this->payload),
                ]);
            });
    }
}
