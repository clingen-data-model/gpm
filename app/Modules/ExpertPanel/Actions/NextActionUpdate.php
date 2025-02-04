<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Foundation\Bus\Dispatchable;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionUpdated;

class NextActionUpdate
{
    use AsController;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(
        ExpertPanel $expertPanel,
        NextAction $nextAction,
        ?String $dateCreated = null,
        ?String $dateCompleted = null,
        ?String $targetDate = null,
        ?String $entry = null,
        ?int $step = null,
        ?String $assignedTo = null,
        ?String $assignedToName = null
    ) {
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
        $nextAction->fill($data);
        DB::transaction(fn () => $nextAction->save());
        $nextAction->update($data);

        event(new NextActionUpdated(expertPanel: $expertPanel, nextAction: $nextAction, oldData: $oldData));

        return $nextAction->load('assignee');
    }

    public function asController(ActionRequest $request, ExpertPanel $expertPanel, NextAction $nextAction)
    {
        $params = $request->except('id', 'uuid');
        $cmdParams = [
            'expertPanel' => $expertPanel,
            'nextAction' => $nextAction
        ];
        foreach ($params as $key => $value) {
            $cmdParams[Str::camel($key)] = $value;
        }

        return $this->handle(...$cmdParams);
    }
}
