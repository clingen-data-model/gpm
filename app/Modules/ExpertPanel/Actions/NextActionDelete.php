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
     */
    public function handle(ExpertPanel $expertPanel, $nextActionId): void
    {
        $expertPanel
            ->nextActions()
            ->findOrFail($nextActionId)
            ->delete();
    }
}
