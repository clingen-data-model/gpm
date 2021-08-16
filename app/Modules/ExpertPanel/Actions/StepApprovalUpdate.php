<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepDateApprovedUpdated;

class StepApprovalUpdate
{
    use AsAction;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(string $expertPanelUuid, int $step, string $dateApproved)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $approvalDates = $expertPanel->approval_dates;
        $approvalDates['step '.$step] = $dateApproved;
        $expertPanel->approval_dates = $approvalDates;

        DB::transaction(function () use ($expertPanel, $step, $dateApproved) {
            $expertPanel->save();

            Event::dispatch(new StepDateApprovedUpdated(application: $expertPanel, step: $step, dateApproved: $dateApproved));
        });
    }
}
