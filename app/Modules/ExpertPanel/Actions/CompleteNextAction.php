<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionCompleted;

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
     *
     * @return void
     */
    public function handle()
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($this->expertPanelUuid);
        $nextAction = NextAction::findByUuidOrFail($this->nextActionUuid);

        $nextAction->date_completed = $this->dateCompleted;
        $nextAction->save();
        $expertPanel->touch();

        Event::dispatch(new NextActionCompleted(application: $expertPanel, nextAction: $nextAction));
    }
}
