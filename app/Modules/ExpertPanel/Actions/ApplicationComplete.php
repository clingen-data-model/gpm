<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;

class ApplicationComplete
{
    use AsAction;

    public function __construct(private StepManagerFactory $stepManagerFactory)
    {
    }

    public function handle(ExpertPanel $expertPanel, Carbon $dateCompleted)
    {
        $expertPanel->refresh();
        $stepManager = ($this->stepManagerFactory)($expertPanel);
        if (!$stepManager->isLastStep()) return;
        if ($expertPanel->date_completed !== null) return;
        $expertPanel->date_completed = $dateCompleted;
        $expertPanel->save();

        Event::dispatch(new ApplicationCompleted($expertPanel));
    }
}
