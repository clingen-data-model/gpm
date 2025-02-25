<?php

namespace App\Console\Commands;

use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\GroupDescriptionUpdated;
use App\Modules\Group\Models\Group;
use Illuminate\Console\Command;
use League\CommonMark\CommonMarkConverter;

class ConvertDescriptionsToHtml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixup:convert-descriptions-to-html';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes all website descriptions, scope descriptions, and membership descriptions, converting from markdown to HTML.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $converter = new CommonMarkConverter();
        ExpertPanel::all()->each(function ($ep) use ($converter) {
            $toUpdate = [];
            if ($ep->scope_description) {
                $toUpdate['scope_description'] = $converter->convertToHtml($ep->scope_description);
            }
            if ($ep->membership_description) {
                $toUpdate['membership_description'] = $converter->convertToHtml($ep->membership_description);
            }
            if (!empty($toUpdate)) {
                $ep->update($toUpdate);
                event(new ExpertPanelAttributesUpdated($ep, $toUpdate));
            };
        });

        Group::whereNotNull('description')->each(function ($group) use ($converter) {
            $newDescription = $converter->convertToHtml($group->description);
            $group->update($toUpdate = ['description' => $newDescription]);
            event(new GroupDescriptionUpdated($group, $newDescription, $group->description));
        });
    }
}
