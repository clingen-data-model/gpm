<?php

namespace App\Modules\Group\Notifications;

use App\Modules\Group\Models\Submission;
use Illuminate\Bus\Queueable;
use App\Modules\Group\Models\Group;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AddedToGroupNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Group $group)
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                ->subject('You have been added to to '.$this->group->displayName)
                ->view('email.added_to_group', [
                    'notifiable' => $notifiable,
                    'group' => $this->group
                ]);
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
            'message' => 'You have been added to '.$this->group->displayName.'.',
            'group' => $this->group->toArray()
        ];
    }
}
