<?php

namespace App\DataExchange\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\DataSynchronizationException;
use App\Modules\ExpertPanel\Actions\SpecificationAndRulesetsSync;

class ClassifiedRulesApprovedProcessor
{
    public function __construct(
        private StepApprove $approveStepAction,
        private SpecificationAndRulesetsSync $syncSpecification
    ) {
    }

    public function handle(IncomingStreamMessage $message): void
    {
        $cspecDoc = $message->payload->cspecDoc;
        $expertPanel = ExpertPanel::findByAffiliationId($cspecDoc->affiliationId);
        if (!$expertPanel) {
            Log::error('Received pilot-rules-approved event about EP with unkown affiliation id '.$cspecDoc->affiliationId);
            return;
        }

        if (!$expertPanel->definitionIsApproved) {
            throw new DataSynchronizationException('Received classified-rules-approved message, but expert panel '.$expertPanel->displayName.' is not definition approved.');
        }

        if ($expertPanel->definitionIsApproved && $expertPanel->current_step == 1) {
            throw new DataSynchronizationException('Received classified-rules-approved message, but expert panel '.$expertPanel->displayName.' has current_step == 1 even though has step_1_approval_date.');
        }

        // Sync the specification and rulset models to latest from CSPEC registry
        $this->syncSpecification->handle(
            cspecId: $cspecDoc->cspecId,
            expertPanel: $expertPanel,
            name: $cspecDoc->name,
            status: $cspecDoc->status,
            rulesets: $cspecDoc->ruleSets
        );


        if ($expertPanel->hasApprovedDraft) {
            return;
        }

        $this->approveStepAction->handle(
            expertPanel: $expertPanel,
            dateApproved: new Carbon($cspecDoc->status->modifiedAt ?? $cspecDoc->eventTime ?? 0),
            notifyContacts: true
        );
    }
}
