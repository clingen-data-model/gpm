<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Actions\StepApprovalUpdate;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Notifications\ValueObjects\MailAttachment;
use App\Modules\ExpertPanel\Jobs\UpdateApprovalDate;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Http\Requests\UpdateApprovalDateRequest;
use App\Modules\ExpertPanel\Http\Requests\ApplicationApprovalRequest;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;

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
