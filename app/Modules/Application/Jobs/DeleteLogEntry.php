<?php

namespace App\Modules\Application\Jobs;

use App\Models\Activity;
use InvalidArgumentException;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;

class DeleteLogEntry
{
    use Dispatchable;

    protected Application $applicationUuid;

    protected ?Activity $logEntry;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(String $applicationUuid, Int $logEntryId)
    {
        //
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
        if (!is_null($this->logEntry->activity_type)) {
            throw new InvalidArgumentException('Only manual log entries can be deleted.');
        }

        $this->logEntry->delete();
    }
}
