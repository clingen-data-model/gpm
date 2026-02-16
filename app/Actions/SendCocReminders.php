<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Notifications\CocReminderNotification;

class SendCocReminders
{
    use AsJob, AsCommand;

    public $commandSignature = "coc:send-reminders";

    public function handle()
    {
        Person::query()
            ->isActivatedUser()
            ->where(function ($q) {
                $q->missingCoC()
                ->orWhere(function ($q2) {
                    $q2->hasCocExpired();
                });
            })
            ->withLatestCoc()
            ->get()
            ->each(function ($person) {
                $reason = $person->latestCocAttestation ? 'expired' : 'missing';

                $person->notify(new CocReminderNotification(
                    subject: 'ClinGen Code of Conduct - Action Required',
                    view: 'email.coc_expired',
                    viewData: ['reason' => $reason],
                ));
            });
    }

}
