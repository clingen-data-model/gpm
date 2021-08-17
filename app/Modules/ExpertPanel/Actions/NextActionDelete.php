<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\NextAction;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class NextActionDelete
{
    use AsAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(string $expertPanelUuid, $nextActionId)
    {
        ExpertPanel::findByUuidOrFail($expertPanelUuid)
            ->nextActions()
            ->findOrFail($nextActionId)
            ->delete();
    }
}
