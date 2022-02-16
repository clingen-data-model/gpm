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
        $people = Person::query()
            ->hasPendingCois()
            ->with('membershipsWithPendingCoi')
            ->get();

        // dd($people->count());

        $people->each(function ($person) {
            $person->notify(new CoiReminderNotification);
        });
    }
}
