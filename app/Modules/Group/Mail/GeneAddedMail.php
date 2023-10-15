<?php

namespace App\Modules\Group\Mail;

use App\Modules\Group\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GeneAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Collection $genes)
    {
    }

    /**
     * Build the message.
     */
    public function build(): static
    {
        return $this->view('email.gene_added');
    }
}
