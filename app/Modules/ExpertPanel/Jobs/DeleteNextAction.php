<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\NextAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class DeleteNextAction
{
    use Dispatchable;

    protected ExpertPanel  $application;

    protected NextAction $nextAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $applicationUuid, $nextActionId)
    {
        //
        $this->application = ExpertPanel::findByUuidOrFail($applicationUuid);
        $this->nextAction = $this->application->nextActions()->findOrFail($nextActionId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->nextAction->delete();
    }
}
