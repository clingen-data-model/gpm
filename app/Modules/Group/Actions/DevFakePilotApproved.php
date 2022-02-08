<?php

namespace App\Modules\Group\Actions;

use App\Tasks\Actions\TaskCreate;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\Concerns\AsController;

class DevFakePilotApproved
{
    use AsController;

    public function handle(Group $group)
    {
        if (app()->environment('production')) {
            return response('Refusing to create a task to review sustained curation b/c you are in a production system.', 418);
        }

        $action = app()->make(TaskCreate::class);
        $task = $action->handle($group, config('tasks.types.sustained-curation-review.id'));
        return $task;
    }
}
