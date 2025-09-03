<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Group\Models\Group;

class CheckpointGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkpoint:groups {groups?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $group_ids = $this->argument('groups');
        if (count($group_ids) > 0) {
            $groups = Group::whereIn('id', $group_ids)->get();
        } else {
            $groups = Group::all();
        }
        $groups->each(function ($group) {
            $this->info('Checkpointing group: ' . $group->name);
            event(new \App\Modules\Group\Events\GroupCheckpointEvent($group));
        });
    }
}
