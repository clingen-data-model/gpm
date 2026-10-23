<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\Group\Events\ApplicationStepSubmitted;

class ApplicationSubmissionAssignNextAction
{
    use AsListener;

    public function __construct(private NextActionCreate $createNextAction)
    {
    }
    

    public function handle(Group $group, ?Submission $submission = null)
    {
        $isRevision = $submission?->scope_of_work_version_id !== null;
        $assignedTo = $this->resolveAssignedGroup($group);
        $this->createNextAction
            ->handle(
                expertPanel: $group->expertPanel,
                uuid: Uuid::uuid4()->toString(),
                dateCreated: Carbon::now(),
                entry: $isRevision ? 'Review Scope of Work revision and respond to EP.' : 'Review application and respond to EP.',
                step: $isRevision ? data_get($submission->data, 'approval_step') : null,
                targetDate: Carbon::now()->addDays(14),
                 assignedTo: $assignedTo,
                typeId: config('next_actions.types.review-submission.id')
            );
    }
    
    public function asListener(ApplicationStepSubmitted $event)
    {
        $this->handle($event->submission->group, $event->submission);
    }

    private function resolveAssignedGroup(Group $group): int
    {
        if ($group->expertPanel->isScvcep) {
            return config('next_actions.assignees.somatic-curation-core-group.id');
        }

        if ($group->expertPanel->isVcep) {
            return config('next_actions.assignees.cdwg-oc.id');
        }

        return config('next_actions.assignees.gene-curation-core-group.id');
    }
    
}
