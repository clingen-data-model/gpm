<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\NextAction;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionAdded;
use App\Modules\ExpertPanel\Http\Requests\CreateNextActionRequest;

class NextActionCreate
{
    use AsAction;
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        string $expertPanelUuid,
        string $uuid,
        string $entry,
        string $dateCreated,
        ?string $dateCompleted = null,
        ?string $targetDate = null,
        ?int $step = null,
        ?string $assignedTo = null,
        ?string $assignedToName = null,
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $nextAction = NextAction::make([
            'uuid' => $uuid,
            'entry' => $entry,
            'date_created' => $dateCreated,
            'target_date' => $targetDate,
            'step' => $step,
            'date_completed' => $dateCompleted,
            'assigned_to' => $assignedTo,
            'assigned_to_name' => $assignedToName
        ]);

        $expertPanel->nextActions()->save($nextAction);
        $expertPanel->touch();
        
        Event::dispatch(new NextActionAdded($expertPanel, $nextAction));
        
        return $nextAction;
    }

    public function asController($expertPanelUuid, CreateNextActionRequest $request)
    {
        $na = $this->handle(
            expertPanelUuid: $expertPanelUuid,
            uuid: $request->uuid,
            entry: $request->entry,
            dateCreated: $request->date_created,
            dateCompleted: $request->date_completed,
            targetDate: $request->target_date,
            step: $request->step,
            assignedTo: $request->assigned_to,
            assignedToName: $request->assigned_to_name
        );

        return response($na, 200);
    }
}
