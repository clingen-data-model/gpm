<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ExpertPanelDeleted;

class DeleteExpertPanel
{
    use Dispatchable;

    private ExpertPanel  $application;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(String $applicationUuid)
    {
        $this->application = ExpertPanel::findByUuidOrFail($applicationUuid);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->application->delete();

        event(new ExpertPanelDeleted(application: $this->application));
    }
}
