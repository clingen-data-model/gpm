<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionAdded;
use App\Modules\ExpertPanel\Http\Requests\CreateNextActionRequest;
use Ramsey\Uuid\Uuid;

class NextActionCreate
{
    use AsAction;
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        ExpertPanel $expertPanel,
        string $entry,
        string $dateCreated,
        ?string $uuid = null,
        ?string $dateCompleted = null,
        ?string $targetDate = null,
        ?int $step = null,
        ?string $assignedTo = null,
        ?string $assignedToName = null,
        ?int $typeId = null
    ): NextAction {
        $nextAction = NextAction::make([
            'uuid' => $uuid ?? Uuid::uuid4()->toString(),
            'entry' => $entry,
            'date_created' => $dateCreated,
            'target_date' => $targetDate,
            'application_step' => $step,
            'date_completed' => $dateCompleted,
            'assignee_id' => $assignedTo ?? 1,
            'assignee_name' => $assignedToName,
            'type_id' => $typeId
        ]);

        $expertPanel->nextActions()->save($nextAction);
        $expertPanel->touch();
        
        Event::dispatch(new NextActionAdded($expertPanel, $nextAction));
        
        return $nextAction;
    }

    public function asController(ExpertPanel $expertPanel, CreateNextActionRequest $request)
    {
        $na = $this->handle(
            expertPanel: $expertPanel,
            uuid: $request->uuid,
            entry: $request->entry,
            dateCreated: $request->date_created,
            dateCompleted: $request->date_completed,
            targetDate: $request->target_date,
            step: $request->step,
            assignedTo: $request->assigned_to,
            assignedToName: $request->assigned_to_name
        );

        return response($na->load('assignee'), 200);
    }
}
