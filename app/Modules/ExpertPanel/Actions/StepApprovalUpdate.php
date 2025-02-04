<?php

namespace App\Modules\ExpertPanel\Actions;

use Carbon\Carbon;
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
        $expertPanel->{'step_'.$step.'_approval_date'} = $dateApproved;

        DB::transaction(function () use ($expertPanel, $step, $dateApproved) {
            $expertPanel->save();

            Event::dispatch(
                new StepDateApprovedUpdated(
                    expertPanel: $expertPanel,
                    step: $step,
                    dateApproved: $dateApproved
                )
            );
        });
    }
}
