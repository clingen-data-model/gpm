<?php

namespace App\Modules\Application\Notifications;

use App\Modules\Application\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStepApprovedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public Application $application, public int $approvedStep, public bool $wasLastStep)
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
        $stepMessages = [
            1 => 'applications.email.approval.initial_approval',
            2 => 'applications.email.approval.vcep_step_2_approval',
            3 => 'applications.email.approval.vcep_step_3_approval',
            4 => 'applications.email.approval.vcep_step_4_approval',
        ];

        if (!isset($stepMessages[$this->approvedStep])) {
            return;
        }

        $mailMessage = (new MailMessage)
                    ->subject('Application step '.$this->approvedStep.' for your ClinGen expert panel '.$this->application->name.' has been approved.')
                    ->view($stepMessages[$this->approvedStep], [
                        'notifiable' => $notifiable,
                        'application' => $this->application,
                        'approvedStep' => $this->approvedStep,
                        'wasLastStep' => $this->wasLastStep
                    ]);
        if ($this->approvedStep === 1) {
            foreach (config('applications.cc_on_step_approved') as $cc) {
                $mailMessage->cc($cc[0], $cc[1]);
            }
        }
        return $mailMessage;
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
