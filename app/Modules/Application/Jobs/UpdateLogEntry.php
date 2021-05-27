<?php

namespace App\Modules\Application\Jobs;

use App\Models\Activity;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateLogEntry
{
    use Dispatchable;

    private Application $application;
    private Activity $logEntry;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $applicationUuid,
        int $logEntryId,
        private string $logDate,
        private string $entry,
        private ?int $step = null
    )
    {
        $this->application = Application::findByUuidOrFail($applicationUuid);
        $this->logEntry = $this->application->logEntries()->findOrFail($logEntryId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // dd(get_class($this->logEntry));
        $this->logEntry->created_at = Carbon::parse($this->logDate);
        $this->logEntry->description = $this->entry;
        $props = $this->logEntry->properties;
        $props->put('entry', $this->entry);
        $props->put('step', $this->step);
        $props->put('log_date', $this->logDate);
        $this->logEntry->properties = $props;

        $this->logEntry->save();
    }
}
