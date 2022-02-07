<?php

namespace App\Tasks\Actions;

use App\Tasks\Models\Task;
use App\Tasks\Models\TaskType;
use App\Tasks\Contracts\TaskAssignee;

class TaskCreate
{
    public function handle(TaskAssignee $assignee, $taskType): Task
    {
        $task = new Task([
            'task_type_id' => $this->resolveTaskTypeId($taskType)
        ]);
        
        $task->for($assignee)
            ->save();

        return $task;
    }

    private function resolveTaskTypeId($type)
    {
        if (is_object($type) && get_class($type) == TaskType::class) {
            return $type->id;
        }

        if (is_string($type)) {
            return config('tasks.types.'.$type.'.id');
        }

        return $type;
    }
}
