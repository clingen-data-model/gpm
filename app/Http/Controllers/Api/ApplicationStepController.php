<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\Application\Jobs\ApproveStep;
use App\Modules\Application\Models\Application;
use App\Http\Requests\UpdateApprovalDateRequest;
use App\Http\Requests\ApplicationApprovalRequest;
use App\Modules\Application\Jobs\UpdateApprovalDate;
use App\Modules\Application\Service\StepManagerFactory;
use App\Modules\Application\Exceptions\UnmetStepRequirementsException;

class ApplicationStepController extends Controller
{
    public function __construct(private Dispatcher $dispatcher){}

    public function approve($uuid, ApplicationApprovalRequest $request)
    {
        try {
            $job = new ApproveStep(
                applicationUuid: $uuid, 
                dateApproved: $request->date_approved,
                notifyContacts: ($request->has('notify_contacts')) ? $request->notify_contacts : false
            );
            $this->dispatcher->dispatch($job);

            return Application::findByUuidOrFail($uuid);

        } catch (UnmetStepRequirementsException $e) {
            return response([
                'message' => $e->getMessage(),
                'errors' => $e->getUnmetRequirements(),
            ], 422);
        }
    }

    public function updateApprovalDate($applicationUuid, UpdateApprovalDateRequest $request)
    {
        $job = new UpdateApprovalDate(applicationUuid: $applicationUuid, step: $request->step, dateApproved: $request->date_approved);
        
        $this->dispatcher->dispatch($job);

        return Application::findByUuidOrFail($applicationUuid);
    }
    
    
}
