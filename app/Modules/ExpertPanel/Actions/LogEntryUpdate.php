<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Http\Requests\UpdateApplicationLogEntryRequest;

class LogEntryUpdate
{
    use AsAction;

    public function handle(
        string $expertPanelUuid,
        int $logEntryId,
        string $logDate,
        string $entry,
        ?int $step = null
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $logEntry = $expertPanel->group->logEntries()->findOrFail($logEntryId);

        $logEntry->created_at = Carbon::parse($logDate);
        $logEntry->description = $entry;
        $props = $logEntry->properties;
        $props->put('entry', $entry);
        $props->put('step', $step);
        $props->put('log_date', $logDate);
        $logEntry->properties = $props;

        $logEntry->save();

        return $logEntry;
    }

    public function asController($expertPanelUuid, $logEntryId, UpdateApplicationLogEntryRequest $request)
    {
        $logEntry = $this->handle(
            expertPanelUuid: $expertPanelUuid,
            logEntryId: $logEntryId,
            entry: $request->entry,
            step: $request->step,
            logDate: $request->log_date
        );

        $logEntry->load(['causer']);
        return $logEntry;
    }
}
