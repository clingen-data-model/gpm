<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\ExpertPanel\Jobs\DeleteNextAction;
use App\Modules\ExpertPanel\Jobs\UpdateNextAction;
use App\Modules\ExpertPanel\Jobs\CompleteNextAction;
use App\Modules\ExpertPanel\Actions\NextActionDelete;
use App\Modules\ExpertPanel\Actions\NextActionUpdate;
use App\Modules\ExpertPanel\Actions\NextActionComplete;
use App\Modules\ExpertPanel\Http\Requests\CreateNextActionRequest;
use App\Modules\ExpertPanel\Http\Requests\UpdateNextActionRequest;
use App\Modules\ExpertPanel\Http\Requests\CompleteNextActionRequest;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ApplicationNextActionsController extends Controller
{
    public function __construct(
        private Dispatcher $dispatcher,
        private NextActionComplete $completeAction,
        private NextActionUpdate $updateAction,
        private NextActionDelete $deleteAction
    ) {
    }

    public function update(ExpertPanel $expertPanel, $id, UpdateNextActionRequest $request)
    {
        $params = $request->except('id', 'uuid');
        $cmdParams = [
            'expertPanelUuid' => $expertPanel->uuid,
            'nextActionId' => $id
        ];
        foreach ($params as $key => $value) {
            $cmdParams[Str::camel($key)] = $value;
        }

        $nextAction = $this->updateAction->handle(...$cmdParams);

        return $nextAction;
    }
    

    public function complete($expertPanelUuid, $nextActionUuid, CompleteNextActionRequest $request)
    {
        $nextAction = $this->completeAction->handle(
            expertPanelUuid: $expertPanelUuid,
            nextActionUuid: $nextActionUuid,
            dateCompleted: $request->date_completed
        );

        return $nextAction;
    }

    public function destroy($expertPanelUuid, $id)
    {
        $this->deleteAction->handle(
            expertPanelUuid: $expertPanelUuid,
            nextActionId: $id
        );

        return response(['status' => 'success']);
    }
}
