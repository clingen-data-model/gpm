<?php

namespace App\DataExchange\Actions;

use Carbon\Carbon;
use App\Tasks\Actions\TaskCreate;
use Illuminate\Support\Facades\Log;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\DataSynchronizationException;
use App\Modules\ExpertPanel\Actions\SpecificationAndRulsetsSync;
use App\Modules\ExpertPanel\Actions\SpecificationRulesetSync;
use App\Modules\ExpertPanel\Actions\TaskCreateSustainedCurationReview;

class PilotRulesApprovedProcessor
{
    public function __construct(
        private StepApprove $approveStep,
        private TaskCreate $createTask,
        private SpecificationAndRulsetsSync $syncSpecificationAndRulesets
    ) {
    }


    public function handle(IncomingStreamMessage $message)
    {
        $cspecDoc = $message->payload->cspecDoc;
        $expertPanel = ExpertPanel::findByAffiliationId($cspecDoc->affiliationId);

        if (!$expertPanel) {
            Log::error('Received pilot-rules-approved event about EP with unkown affiliation id '.$cspecDoc->affiliationId);
            return;
        }

        if (!$expertPanel->hasApprovedDraft) {
            throw new DataSynchronizationException('Received classified-rules-approved message, but expert panel '.$expertPanel->displayName.' is not draft approved.');
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
            dateApproved: Carbon::createFromTimestamp($message->timestamp),
            notifyContacts: true
        );
    }
}
