<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\Group\Events\ApplicationRevisionsRequested;

class ApplicationRevisionsRequestedAssignNextAction
{
    use AsListener;
    
    public function __construct(private NextActionCreate $createNextAction)
    {
    }
    

    public function handle(Group $group, ?Submission $submission = null): NextAction
    {
        $typeData = config('next_actions.types.make-revisions');

        return $this->createNextAction->handle(
            expertPanel: $group->expertPanel,
            entry: $submission?->scope_of_work_version_id
                ? 'Update Scope of Work revision and resubmit for approval.' : $typeData['default_entry'],
            step: $submission?->scope_of_work_version_id ? data_get($submission->data, 'approval_step') : null,
            typeId: $typeData['id'],
            assignedTo: config('next_actions.assignees.expert-panel.id'),
            dateCreated: Carbon::now()
        );
    }

    public function asListener(ApplicationRevisionsRequested $event): void
    {
        $this->handle($event->submission->group);
    }
    
}
