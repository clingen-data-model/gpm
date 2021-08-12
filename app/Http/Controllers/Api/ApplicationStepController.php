<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\ExpertPanel\Jobs\ApproveStep;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Http\Requests\UpdateApprovalDateRequest;
use App\Http\Requests\ApplicationApprovalRequest;
use App\Notifications\ValueObjects\MailAttachment;
use App\Modules\ExpertPanel\Jobs\UpdateApprovalDate;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;

class ApplicationStepController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function approve($uuid, ApplicationApprovalRequest $request)
    {
        try {
            $attachments = collect($request->attachments)
            ->map(function ($file) {
                return MailAttachment::createFromUploadedFile($file);
            })
            ->toArray();
            $job = new ApproveStep(
                expertPanelUuid: $uuid,
                dateApproved: $request->date_approved,
                notifyContacts: ($request->has('notify_contacts')) ? $request->notify_contacts : false,
                subject: $request->subject,
                body: $request->body,
                attachments: $attachments
            );
            $this->dispatcher->dispatch($job);

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
        $job = new UpdateApprovalDate(expertPanelUuid: $expertPanelUuid, step: $request->step, dateApproved: $request->date_approved);
        
        $this->dispatcher->dispatch($job);

        return ExpertPanel::findByUuidOrFail($expertPanelUuid);
    }
}
