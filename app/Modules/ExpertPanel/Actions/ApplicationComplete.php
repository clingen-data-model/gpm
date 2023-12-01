<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class ApplicationComplete
{
    use AsAction;

    public function __construct(private StepManagerFactory $stepManagerFactory)
    {
    }

    public function handle(ExpertPanel $expertPanel, Carbon $dateCompleted)
    {
        $stepManager = ($this->stepManagerFactory)($expertPanel);
        if ($stepManager->isLastStep()) {
            $expertPanel->date_completed = $dateCompleted;
            $expertPanel->save();
        }

        Event::dispatch(new ApplicationCompleted($expertPanel));
    }
}
