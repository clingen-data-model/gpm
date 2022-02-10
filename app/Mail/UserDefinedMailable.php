<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDefinedMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        // don't bind b/c "Type of App\Mail\UserDefinedMailable::$subject must not be defined (as in class Illuminate\Mail\Mailable)"
        // String $subject,
        public String $body,
    ) {
        // $this->subject = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.user_defined_email')
            ->with([
                'body' => $this->body
            ]);
    }
}
