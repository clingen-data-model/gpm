<?php

namespace App\Mail;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationStepSubmittedReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $expertPanel)
    {
    }

    /**
     * Build the message.
     */
    public function build(): static
    {
        return $this->view('email.application_step_submitted_receipt', ['expertPanel' => $this->expertPanel]);
    }
}
