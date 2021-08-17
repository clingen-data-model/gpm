<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\NextAction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionUpdated;

class NextActionUpdate
{
    use AsAction;

    protected ExpertPanel  $expertPanel;

    protected NextAction $nextAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(
        string $expertPanelUuid,
        int $nextActionId,
        ?String $dateCreated = null,
        ?String $dateCompleted = null,
        ?String $targetDate = null,
        ?String $entry = null,
        ?int $step = null,
        ?String $assignedTo = null,
        ?String $assignedToName = null
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $nextAction = $expertPanel->nextActions()->find($nextActionId);

        $oldData = $nextAction->toArray();
        $data = [
            'date_created' => $dateCreated,
            'date_completed' => $dateCompleted,
            'target_date' => $targetDate,
            'entry' => $entry,
            'step' => $step,
            'assigned_to' => $assignedTo,
            'assigned_to_name' => $assignedToName
        ];
        DB::transaction(fn () => $nextAction->update($data));
        $nextAction->update($data);

        event(new NextActionUpdated(application: $expertPanel, nextAction: $nextAction, oldData: $oldData));

        return $nextAction;
    }
}
