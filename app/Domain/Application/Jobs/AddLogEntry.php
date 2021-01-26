<?php

namespace App\Domain\Application\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Domain\Application\Models\Application;

class AddLogEntry
{
    use Dispatchable;

    private Application $application;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($applicationUuid, private string $logDate, private string $entry, private ?int $step = null)
    {
        $this->application = Application::findByUuidOrFail($applicationUuid);
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->application->addLogEntry(
            logDate: $this->logDate, 
            entry: $this->entry, 
            step: $this->step
        );
    }
}
