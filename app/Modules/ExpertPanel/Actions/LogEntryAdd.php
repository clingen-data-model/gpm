<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Actions\LogEntryAdd as AppLogEntry;
use App\Modules\ExpertPanel\Http\Requests\CreateApplicationLogEntryRequest;

class LogEntryAdd
{
    use AsAction;

    private ExpertPanel  $expertPanel;

    public function __construct(private AppLogEntry $logEntryAdd)
    {
    }
    

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        string $expertPanelUuid,
        string $logDate,
        string $entry,
        ?int $step = null
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $logEntry = $this->logEntryAdd->handle(subject: $expertPanel->group, logDate: $logDate, entry: $entry, step: $step);

        return $logEntry;
    }

    public function asController($expertPanelUuid, CreateApplicationLogEntryRequest $request)
    {
        $logEntry = $this->handle(
            expertPanelUuid: $expertPanelUuid,
            logDate: $request->log_date,
            entry: $request->entry,
            step: $request->step
        );

        $logEntry->load(['causer']);
        return response($logEntry, 200);
    }
}
