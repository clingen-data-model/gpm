<?php

namespace App\Modules\Group\Actions;

use Illuminate\Contracts\Mail\Mailer;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Mail\ApplicationSubmissionAdminMail;
use App\Modules\Group\Events\ApplicationStepSubmitted;

class ApplicationSubmissionMailAdminGroup
{
    use AsListener;

    public function __construct(private Mailer $mailer)
    {
    }
    

    public function handle(Submission $submission): void
    {
        $mailable = new ApplicationSubmissionAdminMail($submission);
        
        $to = [(object)[
                'name' => 'CDWG Oversight Committee', 
                'email'=> 'cdwg_oversightcommittee@clinicalgenome.org'
            ]];
        if ($submission->group->isGcep) {
            $to = [(object)[
                'name' => 'Gene Curation Working Group', 
                'email'=> 'genecuration@clinicalgenome.org'
            ]];
        }

        $this->mailer
            ->to($to)
            ->send($mailable);
    }

    public function asListener(ApplicationStepSubmitted $event): void
    {
        $this->handle($event->submission);
    }
    
    
}
