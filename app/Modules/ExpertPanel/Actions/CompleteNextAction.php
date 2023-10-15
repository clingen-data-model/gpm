<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\NextActionCompleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Event;

class CompleteNextAction
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private string $expertPanelUuid, private string $nextActionUuid, private string $dateCompleted)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($this->expertPanelUuid);
        $nextAction = NextAction::findByUuidOrFail($this->nextActionUuid);

        $nextAction->date_completed = $this->dateCompleted;
        $nextAction->save();
        $expertPanel->touch();

        Event::dispatch(new NextActionCompleted(application: $expertPanel, nextAction: $nextAction));
    }
}
