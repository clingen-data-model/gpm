<?php

namespace App\Modules\Group\Notifications;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Judgement;
use App\Notifications\Contracts\DigestibleNotificationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class JudgementActivityNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public Group $group,
        public Judgement $judgement,
        public string $event
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        $this->judgement->load('person');
        $this->group->display_name = $this->group->getDisplayNameAttribute();

        return [
            'group' => $this->group,
            'judgement' => $this->judgement,
            'event' => $this->event,
        ];
    }

    public static function getUnique(Collection $collection): Collection
    {
        return $collection->unique(function ($notification) {
            return $notification->data['judgement']['id'];
        });
    }

    /**
     * Filter all messages out if includes deleted event.
     */
    public static function filterInvalid(Collection $collection): Collection
    {
        $includesDeleted = $collection->pluck('data.event')->contains('deleted');
        if ($includesDeleted) {
            return collect();
        }

        return $collection;
    }

    public static function getValidUnique(Collection $collection): Collection
    {
        return static::getUnique(static::filterInvalid($collection));
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.judgement_activity';
    }
}
