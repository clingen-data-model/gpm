<?php

namespace App\Mail;

use App\Modules\Group\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationSubmissionAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public Submission $submission)
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->subject('An application step was submitted.')
            ->view('email.application_step_submitted_admin', ['submission' => $this->submission]);
    }
}
