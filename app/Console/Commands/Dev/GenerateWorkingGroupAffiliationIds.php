<?php

namespace App\Console\Commands\Dev;

use App\Modules\Group\Actions\WorkingGroupAffiliationIdGenerate;
use App\Modules\Group\Models\AffiliationIdSequence;
use App\Modules\Group\Models\Group;
use Illuminate\Console\Command;

class GenerateWorkingGroupAffiliationIds extends Command
{
    protected $signature = 'dev:generate-wg-affiliation-ids
        {--dry-run : Show what would be generated without saving}
        {--limit= : Limit the number of groups processed}
        {--group-id=* : Only process specific group IDs}';

    protected $description = 'Generate missing 6XXXX Affiliation IDs for existing WG, CDWG, and SC-CDWG groups.';

    public function __construct(private WorkingGroupAffiliationIdGenerate $wgAffiliationIdGenerator) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $groupIds = collect($this->option('group-id'))->filter()->map(fn ($id) => (int) $id)->values();

        $query = Group::query()->whereIn('group_type_id', [(int) config('groups.types.wg.id'), (int) config('groups.types.cdwg.id'), (int) config('groups.types.sccdwg.id')])
            ->whereNull('affiliation_id')
            ->orderBy('id');
        if ($groupIds->isNotEmpty()) { $query->whereIn('id', $groupIds); }
        if ($limit) { $query->limit($limit); }
        $groups = $query->get();

        if ($groups->isEmpty()) {
            $this->info('No WG/CDWG/SC-CDWG groups are missing Affiliation IDs.');
            return self::SUCCESS;
        }

        $this->info($groups->count().' group(s) missing Affiliation IDs.');

        if ($dryRun) {
            $this->preview($groups);
            return self::SUCCESS;
        }

        $generated = 0;
        foreach ($groups as $group) {
            $affiliationId = $this->wgAffiliationIdGenerator->handle($group);
            $this->line(sprintf('Generated %s for group %s (%s)', $affiliationId, $group->id, $group->name));
            $generated++;
        }
        $this->info("Generated {$generated} Affiliation ID(s).");

        return self::SUCCESS;
    }

    private function preview($groups): void
    {
        $nextValue = (int) AffiliationIdSequence::query()->where('name', 'working_group')->value('next_value');
        $usedIds = Group::query()->whereNotNull('affiliation_id')->pluck('affiliation_id')->map(fn ($id) => (string) $id)->flip();

        foreach ($groups as $group) {
            while ($nextValue <= 69999 && $usedIds->has((string) $nextValue)) {
                $nextValue++;
            }
            if ($nextValue > 69999) {
                $this->error('The Working Group Affiliation ID range has been exhausted.');
                return;
            }
            $affiliationId = (string) $nextValue;
            $usedIds->put($affiliationId, true);
            $this->line(sprintf('Would generate %s for group %s (%s)', $affiliationId, $group->id, $group->name));
            $nextValue++;
        }
        $this->info('Dry run only. No records were changed.');
    }
}