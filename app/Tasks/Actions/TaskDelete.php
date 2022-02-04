<?php

namespace App\Tasks\Actions;

use App\Tasks\Models\Task;

class TaskDelete
{
    public function handle(Task $task): void
    {
        $task->delete();
    }
}
