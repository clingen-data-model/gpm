<?php

namespace App\Modules\Group\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use App\Modules\Group\Models\Group;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ApplicationSubmissionNotification extends Notification
{
    use Queueable;

    private Group $group;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Submission $submission)
    {
        $this->group = $submission->group;
        $this->expertPanel = $submission->group->expertPanel;
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
                ->subject('An application step was submitted.')
                ->view('email.application_step_submitted', [
                    'notifiable' => $notifiable,
                    'submission' => $this->submission
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
        $this->submission->load('type', 'submitter', 'group');
        return [
            'submission' => $this->submission->toArray()
        ];
    }
}
