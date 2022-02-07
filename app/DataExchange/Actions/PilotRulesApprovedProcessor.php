<?php

namespace App\DataExchange\Actions;

use App\DataExchange\Exceptions\DataSynchronizationException;
use Carbon\Carbon;
use App\Tasks\Actions\TaskCreate;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\DataExchange\Models\IncomingStreamMessage;
use App\Modules\ExpertPanel\Actions\TaskCreateSustainedCurationReview;

class PilotRulesApprovedProcessor
{
    public function __construct(
        private StepApprove $approveStep,
        private TaskCreate $createTask
    ) {
    }
    

    public function handle(IncomingStreamMessage $message)
    {
        $expertPanel = ExpertPanel::findByAffiliationId($message->payload->affiliationId);

        if (!$expertPanel->hasApprovedDraft) {
            throw new DataSynchronizationException('Received classified-rules-approved message, but expert panel '.$expertPanel->displayName.' is not draft approved.');
        }
        
        if ($expertPanel->hasApprovedPilot) {
            $this->createTask->handle($expertPanel->group, config('tasks.types.sustained-curation-review.id'));
            return;
        }

        $this->approveStep->handle(
            expertPanelUuid: $expertPanel->uuid,
            dateApproved: Carbon::createFromTimestamp($message->timestamp),
            notifyContacts: true
        );
    }
}
