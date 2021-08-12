<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\NextAction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionUpdated;

class UpdateNextAction
{
    use Dispatchable;

    protected ExpertPanel  $expertPanel;

    protected NextAction $nextAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $expertPanelUuid,
        int $nextActionId,
        private ?String $dateCreated = null,
        private ?String $dateCompleted = null,
        private ?String $targetDate = null,
        private ?String $entry = null,
        private ?int $step = null,
        private ?String $assignedTo = null,
        private ?String $assignedToName = null
    ) {
        //
        $this->expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $this->nextAction = $this->expertPanel->nextActions()->find($nextActionId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $oldData = $this->nextAction->toArray();
        DB::transaction(function () {
            $updatedData = $this->assembleUpdatedData();
            $this->nextAction->update($updatedData);
        });

        event(new NextActionUpdated(application: $this->expertPanel, nextAction: $this->nextAction, oldData: $oldData));
    }
    
    private function assembleUpdatedData(): array
    {
        $params = get_object_vars($this);
        unset($params['application']);
        unset($params['nextAction']);
        $updated = [];
        foreach (array_filter($params) as $key => $value) {
            $updated[Str::snake($key)] = $value;
        }
        return $updated;
    }
}
