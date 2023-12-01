<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Lorisleiva\Actions\Concerns\AsAction;

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
    public function handle(ExpertPanel $expertPanel, $nextActionId)
    {
        $expertPanel
            ->nextActions()
            ->findOrFail($nextActionId)
            ->delete();
    }
}
