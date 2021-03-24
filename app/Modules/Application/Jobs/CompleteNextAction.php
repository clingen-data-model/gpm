<?php

namespace App\Modules\Application\Jobs;

use App\Models\NextAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\NextActionCompleted;

class CompleteNextAction
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private string $applicationUuid, private string $nextActionUuid, private string $dateCompleted)
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
        $application = Application::findByUuidOrFail($this->applicationUuid);
        $nextAction = NextAction::findByUuidOrFail($this->nextActionUuid);

        $nextAction->date_completed = $this->dateCompleted;
        $nextAction->save();
        $application->touch();

        Event::dispatch(new NextActionCompleted(application: $application, nextAction: $nextAction));
    }
}