<?php

namespace App\Modules\Application\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\ApplicationDeleted;

class DeleteApplication
{
    use Dispatchable;

    private Application $application;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(String $applicationUuid)
    {
        $this->application = Application::findByUuidOrFail($applicationUuid);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->application->delete();

        event(new ApplicationDeleted(application: $this->application));
    }
}
