<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\GroupMember;
use App\Notifications\AttestationReminderNotification;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsCommand;

class SendAttestationReminders
{
    use AsJob, AsCommand;

    public string $commandSignature = 'attestation:send-reminders';

    public function handle(): void
    {
        $coreRoleID = (int) config('groups.roles.core-approval-member.id', 105);
        $vcepTypeID = (int) config('groups.types.vcep.id', 4);

        Person::query()->isActivatedUser()->whereHas('attestation', function ($q) {
                $q->whereNull('revoked_at')->whereNull('deleted_at')->whereNull('attested_at');
        })
        ->whereHas('memberships', function ($q) use ($coreRoleID, $vcepTypeID) {
            $q->whereNull('end_date')
                ->whereHas('group', fn ($g) => $g->where('group_type_id', $vcepTypeID))
                ->whereHas('roles', fn ($r) => $r->where('id', $coreRoleID));
        })
        ->with([
            'memberships' => function ($q) use ($coreRoleID, $vcepTypeID) {
                $q->whereNull('end_date')
                    ->whereHas('group', fn ($g) => $g->where('group_type_id', $vcepTypeID))
                    ->whereHas('roles', fn ($r) => $r->where('id', $coreRoleID))
                    ->with('group:id,name');
            },
        ])
        ->orderBy('id')
        ->chunkById(500, function ($people) {
            foreach ($people as $person) {
                $vcepNames = $person->memberships->pluck('group.name')->filter()->unique()->values()->all();
                $person->notify(new AttestationReminderNotification($vcepNames));
            }
        });
    }
}
