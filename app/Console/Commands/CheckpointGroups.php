<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Actions\EmitGroupCheckpoints;

class CheckpointGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkpoint:groups {groups?*} {--sync : Emit synchronously instead of queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emit group checkpoint events to the Data Exchange for specified groups or all groups if none specified';

    /**
     * Execute the console command.
     */
    public function handle(EmitGroupCheckpoints $emit)
    {
        $group_ids = $this->argument('groups') ?? [];
        if (count($group_ids) > 0) {
            $groups = Group::whereIn('id', $group_ids)->get();
        } else {
            $groups = Group::all();
        }

        $res = $emit->handle($groups, !$this->option('sync'));
        $this->info(($this->option('sync') ? 'Emitted ' : 'Queued ').$res['accepted'].' checkpoint(s).');

        return self::SUCCESS;
    }
}
