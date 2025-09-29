<?php 

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\SyncGtMembersAction;

class SyncGtMembers extends Command
{
    protected $signature = 'gt:sync-members {affiliation_id : ClinGen affiliation id} {--mode=add}';
    protected $description = 'Sync coordinators/biocurators to GeneTracker for a given affiliation';

    public function handle(SyncGtMembersAction $action)
    {
        $affId = (int) $this->argument('affiliation_id');
        $mode  = $this->option('mode');

        /** @var ExpertPanel|null $ep */
        $ep = ExpertPanel::where('affiliation_id', $affId)->first();
        if (!$ep) {
            $this->error("ExpertPanel not found in GPM for affiliation_id={$affId}");
            return self::FAILURE;
        }

        $res = $action->handle($ep, $mode);
        $this->info('Done: '.json_encode($res));
        return self::SUCCESS;
    }
}
