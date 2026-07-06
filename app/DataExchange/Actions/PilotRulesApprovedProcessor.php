<?php

namespace App\DataExchange\Actions;

use Carbon\Carbon;
use App\Tasks\Actions\TaskCreate;
use Illuminate\Support\Facades\Log;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\DataSynchronizationException;
use App\Modules\ExpertPanel\Actions\SpecificationAndRulesetsSync;

class PilotRulesApprovedProcessor
{
    public function __construct(
        private StepApprove $approveStep,
        private TaskCreate $createTask,
        private SpecificationAndRulesetsSync $syncSpecificationAndRulesets
    ) {
    }


    public function handle(IncomingStreamMessage $message)
    {
        $cspecDoc = $message->payload->cspecDoc;
        $group = Group::findByAffiliationId($cspecDoc->affiliationId);
        $expertPanel = $group?->expertPanel;

        if (!$expertPanel) {
            Log::error('Received pilot-rules-approved event about EP with unknown affiliation id '.$cspecDoc->affiliationId);
            return;
        }

        if (!$expertPanel->hasApprovedDraft) {
            throw new DataSynchronizationException('Received pilot-rules-approved message, but expert panel '.$expertPanel->displayName.' is not draft approved.');
        }

        // Sync the specification and rulset models to latest from CSPEC registry
        $this->syncSpecificationAndRulesets->handle(
            cspecId: $cspecDoc->cspecId,
            expertPanel: $expertPanel,
            name: $cspecDoc->name,
            status: $cspecDoc->status,
            rulesets: $cspecDoc->ruleSets
        );

        if ($expertPanel->hasApprovedPilot) {
            $this->createTask->handle($expertPanel->group, config('tasks.types.sustained-curation-review.id'));
            return;
        }

        $this->approveStep->handle(
            expertPanel: $expertPanel,
            dateApproved: new Carbon($cspecDoc->status->modifiedAt ?? $cspecDoc->eventTime ?? 0),
            notifyContacts: true
        );
    }
}
