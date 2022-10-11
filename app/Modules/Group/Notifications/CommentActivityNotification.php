<?php

namespace App\Modules\Group\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Contracts\DigestibleNotificationInterface;

Enum CommentEvent
{
    case created;
    case updated;
    case deleted;
    case resolved;
}

class CommentActivityNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Comment $comment, public string $event)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $this->comment->load('creator');
        $this->group->display_name = $this->group->getDisplayNameAttribute();
        return [
            'group' => $this->group,
            'comment' => $this->comment,
            'event' => $this->event
        ];
    }

    public static function getUnique(Collection $collection):Collection
    {
        $unique = $collection->unique(function ($notification) {
            return $notification->data['comment']['id'];
        });

        return $unique;
    }

    /**
     * Filter all messages out if includes deleted event.
     */
    public static function filterInvalid(Collection $collection):Collection
    {
        $includesDeleted = $collection->pluck('data.event')->contains('deleted');
        if ($includesDeleted) {
            return collect();
        }

        return $collection;
    }

    static public function getValidUnique(Collection $collection): Collection
    {
        $valid = static::filterInvalid($collection);
        $validUnique = static::getUnique($valid);

        return $validUnique;
    }

    static public function getDigestTemplate(): string
    {
        return 'email.digest.comment_activity';
    }
}
