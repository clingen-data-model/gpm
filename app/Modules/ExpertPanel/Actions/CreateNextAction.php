<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\NextAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionAdded;

class CreateNextAction
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $expertPanelUuid,
        private string $uuid,
        private string $entry,
        private string $dateCreated,
        private ?string $dateCompleted = null,
        private ?string $targetDate = null,
        private ?int $step = null,
        private ?string $assignedTo = null,
        private ?string $assignedToName = null,
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($this->expertPanelUuid);
        $nextAction = NextAction::make([
            'uuid' => $this->uuid,
            'entry' => $this->entry,
            'date_created' => $this->dateCreated,
            'target_date' => $this->targetDate,
            'step' => $this->step,
            'date_completed' => $this->dateCompleted,
            'assigned_to' => $this->assignedTo,
            'assigned_to_name' => $this->assignedToName
        ]);

        $expertPanel->nextActions()->save($nextAction);
        $expertPanel->touch();
        
        Event::dispatch(new NextActionAdded($expertPanel, $nextAction));
    }
}
