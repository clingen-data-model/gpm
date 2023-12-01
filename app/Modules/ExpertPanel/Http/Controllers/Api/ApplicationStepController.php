<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Actions\StepApprovalUpdate;
use App\Modules\ExpertPanel\Http\Requests\UpdateApprovalDateRequest;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ApplicationStepController extends Controller
{
    public function __construct(
        private StepApprovalUpdate $stepApprovalUpdateAction
    ) {
    }

    public function updateApprovalDate($expertPanelUuid, UpdateApprovalDateRequest $request)
    {
        $this->stepApprovalUpdateAction->handle(
            expertPanelUuid: $expertPanelUuid,
            step: $request->step,
            dateApproved: $request->date_approved
        );

        return ExpertPanel::findByUuidOrFail($expertPanelUuid);
    }
}
