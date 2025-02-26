<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\GroupDescriptionUpdated;
use Illuminate\Support\Facades\Http;

class ImportLegacyWebsiteDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixup:import-legacy-website-descriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the (pre-Feb-2025) website descriptions from the old website and import them into group->description fields.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $vcep_json = Http::get('https://clinicalgenome.org/data-pull/vceps/')->json();
        foreach ($vcep_json as $vcep) {
            $ep = ExpertPanel::findByAffiliationId($vcep['affiliation_id']);
            if (!$ep) {
                info('Expert Panel not found for ' . $vcep['affiliation_id']);
                continue;
            }
            if (!$ep->group->description) {
                $ep->group->description = $vcep['description'];
                event(new GroupDescriptionUpdated($ep->group, $vcep['description'], null));
                $ep->group->save();
                info('Imported group description for ' . $ep->group->name);
            } else {
                info('Group description already set for ' . $ep->group->name);
            }
        }

        $gcep_json = Http::get('https://clinicalgenome.org/data-pull/gceps/')->json();
        foreach ($gcep_json as $gcep) {
            $ep = ExpertPanel::findByAffiliationId($gcep['affiliation_id']);
            if (!$ep) {
                info('Expert Panel not found for ' . $gcep['affiliation_id']);
                continue;
            }
            if (!$ep->group->description) {
                $ep->group->description = $gcep['description'];
                event(new GroupDescriptionUpdated($ep->group, $gcep['description'], null));
                $ep->group->save();
                info('Imported group description for ' . $ep->group->name);
            } else {
                info('Group description already set for ' . $ep->group->name);
            }
        }

        $cdwg_json = Http::get('https://clinicalgenome.org/data-pull/cwdgs/')->json(); // NOTE: misspelled endpoint!
        foreach ($cdwg_json as $cdwg) {
            $group = Group::where('name', substr($cdwg['title'], 0, -5))->first(); // need to remove " CDWG" from the end of remote name
            if (!$group) {
                info('Group not found for ' . $cdwg['title']);
                continue;
            }
            if (!$group->description) {
                $group->description = $cdwg['description'];
                event(new GroupDescriptionUpdated($group, $cdwg['description'], null));
                $group->save();
                info('Imported group description for ' . $group->name);
            } else {
                info('Group description already set for ' . $group->name);
            }
        }

        $wg_json = Http::get('https://clinicalgenome.org/data-pull/wgs/')->json();
        foreach ($wg_json as $wg) {
            $group = Group::where('name', $wg['title'])->first();
            if (!$group) {
                info('Group not found for ' . $wg['title']);
                continue;
            }
            if (!$group->description) {
                $group->description = $wg['description'];
                event(new GroupDescriptionUpdated($group, $wg['description'], null));
                $group->save();
                info('Imported group description for ' . $group->name);
            } else {
                info('Group description already set for ' . $group->name);
            }
        }
    }
}
