<?php

namespace App\Domain\Application\Jobs;

use App\Models\NextAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Domain\Application\Models\Application;

class CreateNextAction
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $applicationUuid, 
        private string $uuid, 
        private string $entry, 
        private string $dateCreated, 
        private ?string $dateCompleted = null, 
        private ?string $targetDate = null, 
        private ?int $step = null)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $application = Application::findByUuidOrFail($this->applicationUuid);
        $nextAction = NextAction::make([
            'uuid' => $this->uuid,
            'entry' => $this->entry,
            'date_created' => $this->dateCreated,
            'target_date' => $this->targetDate,
            'step' => $this->step,
            'date_completed' => $this->dateCompleted
        ]);

        $application->addNextAction($nextAction);

    }
}
