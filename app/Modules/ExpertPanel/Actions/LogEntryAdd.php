<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Http\Requests\CreateApplicationLogEntryRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class LogEntryAdd
{
    use AsAction;

    private ExpertPanel  $expertPanel;

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
        $logEntry = activity('applications')
            ->performedOn($expertPanel)
            ->createdAt(Carbon::parse($logDate))
            ->causedBy(Auth::user())
            ->withProperties([
                'entry' => $entry,
                'log_date' => $logDate,
                'step' => $step
            ])->log($entry);
        $expertPanel->touch();

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
