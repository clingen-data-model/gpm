<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Actions\MemberRemove;
use App\Notifications\InviteExpirySummaryForCoordinators;

class ExpireGroupInvites extends Command
{
    protected $signature = 'invites:expire-group';
    protected $description = 'Expire pending invites older than TTL; remove invitees from all groups; email a single summary per group to coordinators.';

    public function handle(): int
    {
        $ttlDays = (int) config('app.invitation_ttl_days', 30);
        $cutoff  = CarbonImmutable::now()->subDays($ttlDays);

        $expiredInvites = DB::table('invites')
                            ->whereNull('deleted_at')
                            ->whereNull('redeemed_at')
                            ->where('created_at', '<=', $cutoff)
                            ->select(['id', 'person_id', 'email', 'created_at'])
                            ->orderBy('id')
                            ->get()
                            ->groupBy('person_id');

        $this->info("Found {$expiredInvites->count()} expired invitee(s).");

        $removedByGroup = [];
        $errors = 0;

        foreach ($expiredInvites as $personId => $invitesForPerson) {
            try {
                $memberships = GroupMember::query()
                                ->with([
                                    'group:id,uuid,name,caption,group_status_id,group_type_id',
                                    'group.groupStatus:id,name,updated_at',
                                    'group.type:id,name',
                                    'person:id,first_name,last_name,email',
                                ])
                                ->where('person_id', $personId)
                                ->whereNull('deleted_at')
                                ->whereNull('end_date')
                                ->get();

                foreach ($memberships as $gm) {
                    MemberRemove::run($gm, now()->startOfDay());

                    $removedByGroup[$gm->group_id][] = [
                        'person_id' => $gm->person_id,
                        'name'      => trim(($gm->person->first_name ?? '').' '.($gm->person->last_name ?? '')) ?: "Person #{$gm->person_id}",
                        'email'     => $gm->person->email ?? '(no email)',
                        'invite_created_at' => optional($invitesForPerson->min('created_at'))->toDateTimeString(),
                    ];
                }
                DB::table('invites')->whereIn('id', $invitesForPerson->pluck('id'))->update(['deleted_at' => now()]);

            } catch (\Throwable $e) {
                $errors++;
                report($e);
            }
        }

        foreach ($removedByGroup as $groupId => $rows) {
            $coordinators = $this->coordinatorUsersForGroup($groupId);
            if ($coordinators->isEmpty()) {
                continue;
            }

            $groupName = optional(GroupMember::query()
                ->with('group:id,name')
                ->where('group_id', $groupId)
                ->first())->group->name ?? "Group #{$groupId}";

            Notification::send($coordinators, new InviteExpirySummaryForCoordinators(
                                                    groupId: $groupId,
                                                    groupName: $groupName,
                                                    removedRows: $rows,
                                                    expiredAt: now()->toDateTimeString(),
                                                    ttlDays: $ttlDays
                                                )
            );
        }

        $removedCount = collect($removedByGroup)->flatten(1)->count();
        $this->info("Done. People processed: {$expiredInvites->count()}, Memberships removed: {$removedCount}, Errors: {$errors}");

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
