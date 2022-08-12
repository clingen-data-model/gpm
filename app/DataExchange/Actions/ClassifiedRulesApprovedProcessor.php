<?php

namespace App\DataExchange\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\DataSynchronizationException;

class ClassifiedRulesApprovedProcessor
{
    public function __construct(private StepApprove $approveStepAction)
    {
    }

    public function handle(IncomingStreamMessage $message): void
    {
        $expertPanel = ExpertPanel::findByAffiliationId($message->payload->affiliationId);
        if (!$expertPanel) {
            Log::error('Received pilot-rules-approved event about EP with unkown affiliation id '.$message->payload->cspecDoc->affiliationId);
            return;
        }

        if (!$expertPanel->definitionIsApproved) {
            throw new DataSynchronizationException('Received classified-rules-approved message, but expert panel '.$expertPanel->displayName.' is not definition approved.');
        }

        if ($expertPanel->definitionIsApproved && $expertPanel->current_step == 1) {
            throw new DataSynchronizationException('Received classified-rules-approved message, but expert panel '.$expertPanel->displayName.' has current_step == 1 even though has step_1_approval_date.');
        }

        if ($expertPanel->hasApprovedDraft) {
            return;
        }

        $this->approveStepAction->handle(
            expertPanel: $expertPanel,
            dateApproved: Carbon::createFromTimestamp($message->timestamp),
            notifyContacts: true
        );
    }
}
