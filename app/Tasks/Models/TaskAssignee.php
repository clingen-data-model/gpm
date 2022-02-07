<?php
namespace App\Tasks\Models;

use App\Tasks\Models\Task;

trait TaskAssignee
{
    public function tasks()
    {
        return $this->morphMany(Task::class, 'assignee');
    }
    public function pendingTasks()
    {
        return $this->morphMany(Task::class, 'assignee')
                ->pending();
    }
    public function completedTasks()
    {
        return $this->morphMany(Task::class, 'assignee')
                ->completed();
    }
}
