<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApprovalDateUpdated;
use App\Modules\ExpertPanel\Events\StepDateApprovedUpdated;

class UpdateApprovalDate
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $expertPanelUuid, private int $step, private string $dateApproved)
    {
        $this->expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
        $approvalDates = $this->expertPanel->approval_dates;
        $approvalDates['step '.$this->step] = $this->dateApproved;
        $this->expertPanel->approval_dates = $approvalDates;

        DB::transaction(function () {
            $this->expertPanel->save();

            Event::dispatch(new StepDateApprovedUpdated(application: $this->expertPanel, step: $this->step, dateApproved: $this->dateApproved));
        });
    }
}
