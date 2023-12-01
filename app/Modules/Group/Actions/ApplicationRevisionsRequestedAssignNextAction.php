<?php

namespace App\Modules\Group\Actions;

use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\Group\Events\ApplicationRevisionsRequested;
use App\Modules\Group\Models\Group;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsListener;

class ApplicationRevisionsRequestedAssignNextAction
{
    use AsListener;

    public function __construct(private NextActionCreate $createNextAction)
    {
    }

    public function handle(Group $group): NextAction
    {
        $typeData = config('next_actions.types.make-revisions');

        return $this->createNextAction->handle(
            expertPanel: $group->expertPanel,
            entry: $typeData['default_entry'],
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
