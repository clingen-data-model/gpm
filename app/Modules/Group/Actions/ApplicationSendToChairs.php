<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\Group\Events\ApplicationSentToChairs;
use App\Modules\Group\Notifications\ApplicationReadyForApproverReview;

class ApplicationSendToChairs
{
    public function __construct(private NextActionCreate $createNextAction)
    {
    }


    public function handle(Group $group, ?string $additionalComments = null)
    {
        DB::transaction(function () use ($group, $additionalComments) {

            $this->notifyChairs($group);
            $this->createNextActionForChairs($group);

            $submission = $group->latestPendingSubmission;
            if (!$submission) {
                return;
            }

            $submission->update([
                'submission_status_id' => config('submissions.statuses.under-chair-review.id'),
                'sent_to_chairs_at' => Carbon::now(),
                'notes_for_chairs' => $additionalComments
            ]);


            // Create Tasks for each chair (?)
            // NOT YET

            event(new ApplicationSentToChairs(
                group: $group,
                comments: $group->pendingComments,
                additionalComments: $additionalComments
            ));
        });


        return $group;
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('ep-applications-manage');
    }

    private function notifyChairs(Group $group): void
    {
        $chairs = User::permission('ep-applications-approve')
                    ->with('person')
                    ->get()
                    ->pluck('person');

        Notification::send($chairs, new ApplicationReadyForApproverReview($group));
    }

    private function createNextActionForChairs(Group $group): void
    {
        $this->createNextAction->handle(
            expertPanel: $group->expertPanel,
            entry: 'Review Step '.$group->expertPanel->current_step.' application.',
            dateCreated: Carbon::now()->format('Y-m-d H:i:s'),
            assignedTo: config('next_actions.assignees.chairs.id'),
            typeId: config('next_actions.types.chair-review.id'),
        );
    }
}
