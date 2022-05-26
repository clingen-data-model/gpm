<?php

namespace App\Modules\ExpertPanel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\View;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Notifications\Messages\MailMessage;

class ApplicationStepApprovedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public ExpertPanel  $expertPanel,
        public int $approvedStep,
        public ?bool $wasLastStep = false
    ) {
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
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        $template = $this->getTemplate($this->approvedStep);
        if (!$template) {
            return;
        }
        $mailMessage = (new MailMessage)
                    ->subject($this->renderSubject($this->expertPanel))
                    ->view($template, [
                        'expertPanel' => $this->expertPanel,
                    ]);
                    
        if (in_array($this->approvedStep, config('expert-panels.notifications.cc.steps'))) {
            foreach (config('expert-panels.notifications.cc.recipients') as $cc) {
                $mailMessage->cc($cc[0], $cc[1]);
            }

            // Also CC ClinVar if this is step 4.
            if ($this->approvedStep == 4) {
                $mailMessage->cc('clinvar@ncbi.nlm.nih.gov', 'ClinVar');
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
        $message = 'Congratulations! ' . $this->expertPanel->displayName . ' was approved for '.config('expert-panels.steps')[$this->approvedStep].' on '.$this->expertPanel->getApprovalDateForStep($this->approvedStep)->format('m/d/Y').'.';

        if ($this->wasLastStep) {
            $message = 'Congratulations! ' . $this->expertPanel->displayName . ' was given final approval on '.$this->expertPanel->getApprovalDateForStep($this->approvedStep)->format('m/d/Y').'!';
        }

        return [
            'expert_panel' => $this->expertPanel,
            'date_approved' => $this->approvedStep,
            'message' => $message,
            'type' => 'success'
        ];
    }

    public function renderTemplate(ExpertPanel $expertPanel, ?int $step = null): string
    {
        $step = $step ?? $expertPanel->currentStep;
        $template = $this->getTemplate($step);
        $view = View::make($template, compact($expertPanel));
        
        return $view->render();
    }

    public function renderSubject(ExpertPanel $expertPanel, ?int $step = null): string
    {
        $step = $step ?? $expertPanel->current_step;

        return 'Application step '.$step.' for your ClinGen expert panel '.$expertPanel->name.' has been approved.';       
    }

    private function getTemplate($step)
    {
        $stepMessages = [
            1 => 'email.applications.approval.initial_approval',
            2 => 'email.applications.approval.vcep_step_2_approval',
            3 => 'email.applications.approval.vcep_step_3_approval',
            4 => 'email.applications.approval.vcep_step_4_approval',
        ];

        if (!isset($stepMessages[$step])) {
            return null;
        }

        return $stepMessages[$step];
    }
    
}
