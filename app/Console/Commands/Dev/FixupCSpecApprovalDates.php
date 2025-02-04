<?php

namespace App\Console\Commands\Dev;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\DataExchange\Models\IncomingStreamMessage;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepDateApprovedUpdated;

class FixupCSpecApprovalDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixup:cspec-approval-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix historical data of timestamps for cspec steps 2 and 3 approval dates (GPM-371 and GPM-376).';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function updateStepForAllApplications($step, $eventName)
    {
        $updatedRecordCount = 0;

        $mostRecentApprovals = IncomingStreamMessage::select(
            DB::raw("payload->>'$.cspecDoc.affiliationId' as affiliationId"),
            DB::raw("MAX(payload->>'$.cspecDoc.status.modifiedAt') as latestApproved")
        )
            ->where('payload->cspecDoc->status->event', $eventName)
            ->groupBy('affiliationId')
            ->get()
            ->keyBy('affiliationId');

        foreach ($mostRecentApprovals as $affiliationId => $item) {
            $expertPanel = ExpertPanel::findByAffiliationId($affiliationId);
            if ($expertPanel == null) {
                Log::warning('Received message for unknown expert panel: ' . $affiliationId);
                continue;
            }
            $dateApproved = new Carbon($item->latestApproved);
            $oldApprovalDate = new Carbon($expertPanel->{'step_' . $step . '_approval_date'});
            if ($oldApprovalDate->diffInDays($dateApproved, true) <= 1) {
                //Log::info('Approval date for step ' . $step . ' for ' . $expertPanel->displayName . ' is already within 3 days of the new date. Skipping.');
                continue;
            }

            $expertPanel->{'step_' . $step . '_approval_date'} = $dateApproved;

            DB::beginTransaction();
            try {
                $expertPanel->save();
                Event::dispatch(
                    new StepDateApprovedUpdated(
                        expertPanel: $expertPanel,
                        step: $step,
                        dateApproved: $dateApproved
                    )
                );
                DB::commit();
                $updatedRecordCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to update approval date for step ' . $step . ' for ' . $expertPanel->displayName . ': ' . $e->getMessage());
                continue;
            }
        }
        Log::info('Updated ' . $updatedRecordCount . ' records for step ' . $step . ' with event ' . $eventName . '.');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->updateStepForAllApplications(2, 'classified-rules-approved');
        $this->updateStepForAllApplications(3, 'pilot-rules-approved');
    }
}
