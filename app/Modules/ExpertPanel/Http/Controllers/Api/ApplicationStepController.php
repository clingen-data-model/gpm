<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Actions\StepApprovalUpdate;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\ExpertPanel\Actions\StepApprove;
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
        private StepApprove $stepApproveAction,
        private StepApprovalUpdate $stepApprovalUpdateAction
    ) {
    }

    public function approve($uuid, ApplicationApprovalRequest $request)
    {
        try {
            $attachments = collect($request->attachments)
                ->map(function ($file) {
                    return MailAttachment::createFromUploadedFile($file);
                })
                ->toArray();
           
            $this->stepApproveAction->handle(
                expertPanelUuid: $uuid,
                dateApproved: $request->date_approved,
                notifyContacts: ($request->has('notify_contacts')) ? $request->notify_contacts : false,
                subject: $request->subject,
                body: $request->body,
                attachments: $attachments
            );

            return ExpertPanel::findByUuidOrFail($uuid);
        } catch (UnmetStepRequirementsException $e) {
            return response([
                'message' => $e->getMessage(),
                'errors' => $e->getUnmetRequirements(),
            ], 422);
        }
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
