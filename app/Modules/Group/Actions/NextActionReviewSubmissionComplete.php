<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\ExpertPanel\Actions\NextActionComplete;

class NextActionReviewSubmissionComplete
{
    use AsListener;

    public function __construct(private NextActionComplete $completeNextAction)
    {
    }
    

    public function handle(ExpertPanel $expertPanel)
    {
        $nextActions = $expertPanel->nextActions()->ofType(config('next_actions.types.review-submission.id'))->get();
        $nextActions->each(function ($nextAction) use ($expertPanel) {
            $this->completeNextAction->handle(
                expertPanel: $expertPanel, 
                nextAction: $nextAction, 
                dateCompleted: Carbon::now()
            );
        });
    }

    public function asListener(StepApproved $event): void
    {
        $this->handle($event->application);
    }
    
    
}
