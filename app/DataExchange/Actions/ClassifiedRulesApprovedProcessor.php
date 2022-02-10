<?php

namespace App\DataExchange\Actions;

use App\DataExchange\Exceptions\DataSynchronizationException;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\DataExchange\Models\IncomingStreamMessage;
use App\Modules\ExpertPanel\Actions\StepApprove;
use Carbon\Carbon;

class ClassifiedRulesApprovedProcessor
{
    public function __construct(private StepApprove $approveStepAction)
    {
    }
    
    public function handle(IncomingStreamMessage $message): void
    {
        $expertPanel = ExpertPanel::findByAffiliationId($message->payload->affiliationId);

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
