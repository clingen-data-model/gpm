<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Events\ScopeOfWorkReviewCompleted;
use App\Modules\ExpertPanel\Actions\NextActionComplete;

class NextActionScopeOfWorkReviewComplete
{
    use AsListener;

    public function __construct(
        private NextActionComplete $completeNextAction
    ) {
    }

    public function handle(Submission $submission): void
    {
        $expertPanel = $submission->group->expertPanel;

        $approvalStep = (int) data_get(
            $submission->data,
            'approval_step',
            1
        );

        $nextActions = $expertPanel->nextActions()
            ->ofType(config('next_actions.types.chair-review.id'))
            ->whereNull('date_completed')
            ->where('application_step', $approvalStep)
            ->get();

        foreach ($nextActions as $nextAction) {
            $this->completeNextAction->handle(
                expertPanel: $expertPanel,
                nextAction: $nextAction,
                dateCompleted: Carbon::now()
            );
        }
    }

    public function asListener(
        ScopeOfWorkReviewCompleted $event
    ): void {
        $this->handle($event->submission);
    }
}