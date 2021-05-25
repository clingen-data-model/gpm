<?php

namespace App\Modules\Application\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;

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
        $logEntry = activity('applications')
            ->performedOn($this->application)
            ->createdAt(Carbon::parse($this->logDate))
            ->causedBy(Auth::user())
            ->withProperties([
                'entry' => $this->entry,
                'log_date' => $this->logDate,
                'step' => $this->step
            ])->log($this->entry);
        $this->application->touch();

    }
}
