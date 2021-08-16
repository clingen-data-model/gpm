<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class LogEntryAdd
{
    use Dispatchable;

    private ExpertPanel  $expertPanel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
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
}
