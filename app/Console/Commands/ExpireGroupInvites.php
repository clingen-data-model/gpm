<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Actions\MemberRemove;

class ExpireGroupInvites extends Command
{
    protected $signature = 'invites:expire-group';
    protected $description = 'Expire pending group invites older than TTL and remove the corresponding group membership if unambiguous.';

    public function handle(): int
    {
        $ttlDays = (int) config('gpm.invitation_ttl_days', 30);
        $cutoff  = CarbonImmutable::now()->subDays($ttlDays);
        $invites = DB::table('invites')
            ->whereNull('deleted_at')
            ->whereNull('redeemed_at')
            ->where('created_at', '<=', $cutoff)
            ->select(['id', 'person_id', 'email', 'created_at'])
            ->orderBy('id')
            ->get();

        $this->info("Found {$invites->count()} expired invite(s).");

        $removed = 0; $skipped = 0; $errors = 0;

        foreach ($invites as $invite) {
            try {
                $activeMemberships = GroupMember::query()
                    ->where('person_id', $invite->person_id)
                    ->whereNull('deleted_at')
                    ->whereNull('end_date')
                    ->get();

                if ($activeMemberships->count() === 1) {
                    $gm = $activeMemberships->first();
                    MemberRemove::run($gm, now()->startOfDay());
                    $removed++;
                } else {
                    $skipped++;
                }
                DB::table('invites')->where('id', $invite->id)->update(['deleted_at' => now()]);
            } catch (\Throwable $e) {
                $errors++;
                report($e);
            }
        }

        $this->info("Done. Removed: {$removed}, Skipped: {$skipped}, Errors: {$errors}");
        return self::SUCCESS;
    }
}
