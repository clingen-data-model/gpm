<?php

namespace App\Modules\Group\Actions;

use App\Tasks\Models\Task;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use App\Tasks\Actions\TaskComplete;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\SustainedCurationReviewCompleted;

class SustainedCurationReviewComplete
{
    use AsController;

    public function __construct(private TaskComplete $completeTask)
    {
    }
    

    public function handle(Group $group): Collection
    {
        $tasks = $group->tasks()
            ->pending()
            ->hasType('sustained-curation-review')
            ->get();

        $tasks->map(function ($task) {
            return $this->completeTask->handle($task);
        });

        event(new SustainedCurationReviewCompleted($group, $tasks));

        return $tasks;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        return $this->handle($group);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('updateApplicationAttribute', $request->group);
    }
}
