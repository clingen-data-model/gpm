<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Actions\MemberRemove;
use App\Notifications\InviteExpirySummaryForCoordinators;
use App\Modules\User\Models\User;

class ExpireGroupInvites extends Command
{
    protected $signature = 'invites:expire-group';
    protected $description = 'Expire pending invites older than TTL; remove invitees from all groups; email a single summary per group to coordinators.';

    public function handle(): int
    {
        $now = now();
        $ttlDays = (int) config('app.invitation_ttl_days', 30);
        $cutoff  = $now->copy()->subDays($ttlDays);

        // STEP 1: Find pending prospective memberships (person.user_id IS NULL) older than TTL
        $expiredMemberships = GroupMember::query()
            ->whereNull('end_date')
            ->whereNull('deleted_at')
            ->where('created_at', '<=', $cutoff)
            ->whereHas('person', fn ($q) => $q->whereNull('user_id'))
            ->with([
                'group:id,uuid,name,caption,group_status_id,group_type_id',
                'group.groupStatus:id,name,updated_at',
                'group.type:id,name',
                'person:id,first_name,last_name,email,user_id',
            ])
            ->get();

        $this->info("Found {$expiredMemberships->count()} expired membership(s) to remove (TTL={$ttlDays} days).");

        $removedByGroup = [];
        $errors = 0;

        foreach ($expiredMemberships as $gm) {
            try {                
                MemberRemove::run($gm, $now->copy()->startOfDay());

                $baseName = $gm->group?->name ?? "Group #{$gm->group_id}";
                $typeName = $gm->group?->type?->display_name;
                $groupNameById[$gm->group_id] ??= $typeName ? "{$baseName} {$typeName}" : $baseName;
                $removedByGroup[$gm->group_id][] = [
                    'person_id' => $gm->person_id,
                    'name'      => trim(($gm->person->first_name ?? '').' '.($gm->person->last_name ?? '')) ?: "Person #{$gm->person_id}",
                    'email'     => $gm->person->email ?? '(no email)',
                    'invite_created_at' => optional($gm->created_at)?->toDateTimeString(),
                ];
            } catch (\Throwable $e) {
                $errors++;
                report($e);
            }
        }

        $superAdmins = User::role('super-admin')->get();
        foreach ($removedByGroup as $groupId => $rows) {
                $coordinators = $this->coordinatorUsersForGroup($groupId);
                $recipients = $coordinators->isNotEmpty() ? $coordinators : $superAdmins;

            if ($recipients->isEmpty()) { continue; }
            $groupName = $groupNameById[$groupId] ?? "Group #{$groupId}";
            Notification::send($recipients, new InviteExpirySummaryForCoordinators(
                    groupId: $groupId,
                    groupName: $groupName,
                    removedRows: $rows,
                    expiredAt: $now->toDateTimeString(),
                    ttlDays: $ttlDays
                )
            );
        }

        // STEP 2: Soft-delete invites only for people who still haven't redeemed AND have NO remaining pending memberships
        $peopleTouched = $expiredMemberships->pluck('person_id')->unique()->values();

        if ($peopleTouched->isNotEmpty()) {
            $peopleStillPending = GroupMember::query()
                ->whereIn('person_id', $peopleTouched)
                ->whereNull('deleted_at')
                ->whereNull('end_date')
                ->whereHas('person', fn ($q) => $q->whereNull('user_id'))
                ->pluck('person_id')
                ->unique();

            $peopleToInviteCleanup = $peopleTouched->diff($peopleStillPending)->values();

            if ($peopleToInviteCleanup->isNotEmpty()) {
                DB::table('invites')
                    ->whereIn('person_id', $peopleToInviteCleanup)
                    ->whereNull('redeemed_at')
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => $now]);
            }
        }
        return self::SUCCESS;
    }

    private function coordinatorUsersForGroup(int $groupId)
    {
        return GroupMember::query()->isActive()
            ->where('group_id', $groupId)
            ->whereHas('roles', fn($q) => $q->where('name', 'coordinator'))
            ->with(['person.user'])
            ->get()
            ->map(fn($gm) => $gm->person?->user)
            ->filter();
    }
}
