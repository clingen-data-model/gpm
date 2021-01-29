<?php

namespace App\Http\Controllers\Api;

use App\Models\NextAction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Domain\Application\Jobs\CreateNextAction;
use App\Domain\Application\Jobs\CompleteNextAction;
use App\Http\Requests\Applications\CreateNextActionRequest;
use App\Http\Requests\Applications\CompleteNextActionRequest;

class ApplicationNextActionsController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
        //code
    }

    public function store($applicationUuid, CreateNextActionRequest $request)
    {
        $data = array_merge($request->all(), ['applicationuuid' => $applicationUuid]);
        $job = new CreateNextAction(
            applicationUuid: $applicationUuid,
            uuid: $request->uuid,
            entry: $request->entry,
            dateCreated: $request->date_created,
            dateCompleted: $request->date_completed,
            targetDate: $request->target_date,
            step: $request->step,
        );

        $this->dispatcher->dispatch($job);

        $nextAction = NextAction::findByUuid($request->uuid);

        return $nextAction;
    }

    public function complete($applicationUuid, $nextActionUuid, CompleteNextActionRequest $request)
    {
        $job = new CompleteNextAction(
            applicationUuid: $applicationUuid, 
            nextActionUuid: $nextActionUuid, 
            dateCompleted: $request->date_completed
        );
        $this->dispatcher->dispatch($job);

        $nextAction = NextAction::findByUuid($nextActionUuid);

        return $nextAction;
    }    
}
