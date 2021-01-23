<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Domain\Application\Jobs\ApproveStep;
use App\Domain\Application\Models\Application;
use App\Http\Requests\ApplicationApprovalRequest;
use App\Domain\Application\Service\StepManagerFactory;
use App\Domain\Application\Exceptions\UnmetStepRequirementsException;

class ApplicationStepController extends Controller
{
    public function __construct(private Dispatcher $dispatcher){}

    public function approve($uuid, ApplicationApprovalRequest $request)
    {
        $application = Application::findByUuidOrFail($uuid);

        try {
            $dateApproved = $request->date_approved ? Carbon::parse($request->date_approved) :  Carbon::now();
            $job = new ApproveStep($application, dateApproved: $dateApproved);
            $this->dispatcher->dispatch($job);

            return $application->fresh();
        } catch (UnmetStepRequirementsException $e) {
            return response([
                'message' => $e->getMessage(),
                'errors' => $e->getUnmetRequirements(),
            ], 422);
        }
    }
    
}
