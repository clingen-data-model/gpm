<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsJob;
use App\Modules\Group\Models\GroupMember;
use App\Notifications\CoiReminderNotification;
use Lorisleiva\Actions\Concerns\AsCommand;
use Carbon\Carbon;
use App\Modules\Group\Actions\MemberRetire;

class SendCoiReminders
{
    use AsJob, AsCommand;

    public $commandSignature = "coi:send-reminders";

    public function handle()
    {
        // Retire memberships whose CoI has been overdue for more than 60 days.
        $this->retireMembersWithOverdueCoi();

        // Send reminders for everyone who still has a pending CoI.
        Person::query()
            ->isActivatedUser()
            ->hasPendingCois()
            ->with(
                'membershipsWithPendingCoi',
                'membershipsWithPendingCoi.group'
            )
            ->chunkById(200, function ($people) {
                $people->each(function ($person) {
                    $person->notify(new CoiReminderNotification);
                });
            });
    }

    private function retireMembersWithOverdueCoi(): void
    {
        GroupMember::query()
            ->hasPendingCoi()
            ->with(['group', 'latestCoi'])
            ->chunkById(200, function ($memberships) {
                $memberships->each(function (GroupMember $membership) {
                    if (!$membership->coi_retirement_eligible) { return; }
                    app(MemberRetire::class)->handle($membership, Carbon::today(), 'Conflict of Interest disclosure was not completed within 60 days of the due date.');
                });
            });
    }
}
