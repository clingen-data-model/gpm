<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class AddLogEntry
{
    use Dispatchable;

    private ExpertPanel  $expertPanel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($expertPanelUuid, private string $logDate, private string $entry, private ?int $step = null)
    {
        $this->expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
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
            ->performedOn($this->expertPanel)
            ->createdAt(Carbon::parse($this->logDate))
            ->causedBy(Auth::user())
            ->withProperties([
                'entry' => $this->entry,
                'log_date' => $this->logDate,
                'step' => $this->step
            ])->log($this->entry);
        $this->expertPanel->touch();

    }
}
