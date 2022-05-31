<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\Mailable;

class ContactsMail
{
    public function __construct(private Mailer $mailer)
    {
    }
    

    public function handle(Group $group, Mailable $mailable)
    {
        $contacts = $group->contacts()
                        ->with('person')
                        ->get()
                        ->pluck('person');

        if ($contacts->count() == 0) {
            return;
        }

        $this->mailer->to($contacts)->send($mailable);

    }
    
}
