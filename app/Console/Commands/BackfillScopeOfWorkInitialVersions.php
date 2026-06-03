<?php

namespace App\Console\Commands;

use App\Modules\Group\Actions\ScopeOfWork\InitialVersionCreate;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Console\Command;

class BackfillScopeOfWorkInitialVersions extends Command
{
    protected $signature = 'scope-of-work:backfill-initial 
                            {--dry-run : Show what would be created without creating records}
                            {--group-id= : Backfill one specific group ID only}';

    protected $description = 'Create initial approved 1.0 Scope of Work versions and snapshots for approved Expert Panel groups.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $groupId = $this->option('group-id');

        $query = Group::query()
            ->with(['type', 'status', 'expertPanel'])
            ->whereHas('expertPanel')
            ->whereHas('type', function ($q) {
                $q->whereIn('name', ['gcep', 'vcep', 'scvcep']);
            })
            ->whereHas('status', function ($q) {
                $q->where('name', 'active');
            });

        if ($groupId) {
            $query->where('id', $groupId);
        }

        $groups = $query->get();

        if ($groups->isEmpty()) {
            $this->info('No eligible groups found.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        $this->info("Found {$groups->count()} eligible group(s).");

        foreach ($groups as $group) {
            $existing = ScopeOfWorkVersion::forGroup($group)
                ->approved()
                ->exists();

            if ($existing) {
                $skipped++;
                $this->line("Skipping {$group->id} {$group->name}: approved SoW version already exists.");
                continue;
            }

            if ($dryRun) {
                $this->line("[Dry run] Would create 1.0 SoW version for {$group->id} {$group->name}.");
                $created++;
                continue;
            }

            $version = InitialVersionCreate::run($group);

            $created++;
            $this->info("Created SoW version {$version->version_label} for {$group->id} {$group->name}.");
        }

        $this->newLine();
        $this->info("Created: {$created}");
        $this->info("Skipped: {$skipped}");

        return self::SUCCESS;
    }
}