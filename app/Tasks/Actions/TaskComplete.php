<?php

namespace App\Tasks\Actions;

use Carbon\Carbon;
use App\Tasks\Models\Task;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class TaskComplete
{
    use AsController;

    public function handle(Task $task, $completedAt = null): Task
    {
        $completedAt = $completedAt ?? Carbon::now();
        $task->update(['completed_at' => $completedAt]);
        return $task;
    }

    public function asController(ActionRequest $request, Task $task)
    {
        $completedAt = $request->completed_at;

        return $this->handle($task, $completedAt);
    }

    public function rules()
    {
        return [
            'completed_at' => 'nullable|date'
        ];
    }
}
