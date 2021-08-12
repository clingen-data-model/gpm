<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class UpdateLogEntry
{
    use Dispatchable;

    private ExpertPanel  $expertPanel;
    private Activity $logEntry;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $expertPanelUuid,
        int $logEntryId,
        private string $logDate,
        private string $entry,
        private ?int $step = null
    ) {
        $this->expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $this->logEntry = $this->expertPanel->logEntries()->findOrFail($logEntryId);
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
