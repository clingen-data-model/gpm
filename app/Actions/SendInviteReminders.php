<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use App\Notifications\InviteReminderNotification;
use Lorisleiva\Actions\Concerns\AsJob;

class SendInviteReminders
{
    use AsJob;

    public function handle()
    {
        $people = Person::query()
                            ->hasPendingInvite()
                            ->with('invite')
                            ->get();

        $people->each(function ($person) {
            $person->notify(new InviteReminderNotification($person->invite));
        });
    }
}
