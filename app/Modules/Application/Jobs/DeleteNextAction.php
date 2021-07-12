<?php

namespace App\Modules\Application\Jobs;

use App\Models\NextAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;

class DeleteNextAction
{
    use Dispatchable;

    protected Application $application;

    protected NextAction $nextAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $applicationUuid, $nextActionId)
    {
        //
        $this->application = Application::findByUuidOrFail($applicationUuid);
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
