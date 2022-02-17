<?php

namespace App\Actions;

use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Notifications\InviteReminderNotification;
use Lorisleiva\Actions\Concerns\AsJob;

class SendInviteReminders
{
    use AsJob;

    public function handle()
    {
        if (!config('app.features.invite_reminders')) {
            return;
        }
        $invites = Invite::pending()->with('person')->get();

        $invites->each(function ($invite) {
            $invite->person->notify(new InviteReminderNotification($invite));
        });
    }
}
