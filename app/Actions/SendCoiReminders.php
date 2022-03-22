<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsJob;
use App\Modules\Group\Models\GroupMember;
use App\Notifications\CoiReminderNotification;

class SendCoiReminders
{
    use AsJob;

    public function handle()
    {
        if (!config('app.features.coi_reminders')) {
            return;
        }
        
        $people = Person::query()
            ->isActivatedUser()
            ->hasPendingCois()
            ->with('membershipsWithPendingCoi', 'membershipsWithPendingCoi.group')
            ->get();

        $people->each(function ($person) {
            $person->notify(new CoiReminderNotification);
        });
    }
}
