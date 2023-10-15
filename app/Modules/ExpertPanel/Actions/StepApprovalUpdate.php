<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\StepDateApprovedUpdated;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class StepApprovalUpdate
{
    use AsAction;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(string $expertPanelUuid, int $step, string $dateApproved): void
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $expertPanel->{'step_'.$step.'_approval_date'} = $dateApproved;

        DB::transaction(function () use ($expertPanel, $step, $dateApproved) {
            $expertPanel->save();

            Event::dispatch(
                new StepDateApprovedUpdated(
                    application: $expertPanel,
                    step: $step,
                    dateApproved: $dateApproved
                )
            );
        });
    }
}
