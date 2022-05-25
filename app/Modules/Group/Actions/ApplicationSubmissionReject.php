<?php

namespace App\Modules\Group\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsController;
use App\Notifications\ValueObjects\MailAttachment;
use App\Modules\ExpertPanel\Actions\NotifyContacts;

class ApplicationSubmissionReject
{
    use AsController;

    public function __construct(
        private NotifyContacts $notifyContactsAction,
    )
    {
    }
    
    
    public function handle(Group $group, Submission $submission): Submission
    {
        $submission
            ->reject();

        return $submission->fresh();
    }

    public function asController(ActionRequest $request, Group $group, Submission $submission)
    {
        DB::beginTransaction();
        try {
            $submission = $this->handle(
                group: $group, 
                submission: $submission
            );

            $attachments = collect($request->attachments)
                ->map(function ($file) {
                    return MailAttachment::createFromUploadedFile($file);
                })
                ->toArray();

            if ($request->notify_contacts) {
                $this->notifyContactsAction->handle(
                    expertPanel: $group->expertPanel,
                    subject: $request->subject,
                    body: $request->body,
                    attachments: $attachments ?? [],
                    ccAddresses: $request->cc_addresses ?? []
                );
            }

            DB::commit();

            return $submission;
        } catch (\Exception $e) {
            DB::rollback();
            report($e);
            return response('There was a problem requesting revisions for this application this submission.', 500);
        }
    }
    
    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('ep-applications-manage');
    }
}
