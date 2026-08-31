<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsJob;
use App\Modules\Group\Models\GroupMember;
use App\Notifications\CoiReminderNotification;
use Lorisleiva\Actions\Concerns\AsCommand;

class SendCoiReminders
{
    use AsJob, AsCommand;

    public $commandSignature = "coi:send-reminders";

    public function handle()
    {
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
