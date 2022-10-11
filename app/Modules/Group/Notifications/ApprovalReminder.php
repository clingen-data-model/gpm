<?php

namespace App\Modules\Group\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Submission;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Contracts\DigestibleNotificationInterface;

class ApprovalReminder extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Submission $submission, public Person $approver)
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
        return [
            'group' => $this->group,
            'submission' => $this->submission,
            'approver' => $this->approver
        ];
    }

    public static function getUnique(Collection $notifications): Collection
    {
        return $notifications->unique(fn ($n) => $n->group->id);
    }

    public static function filterInvalid(Collection $notifications): Collection
    {
        return $notifications;
        // return $notifications->filter(function ($n) {
        //     $submission = $this->data['submission']['id']
        //     return $n->
        // })
    }

    public static function getValidUnique(Collection $notifications): Collection
    {
        return static::getUnique($notifications);
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.approvalReminder';
    }




}
