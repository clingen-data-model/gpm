<?php

namespace App\Tasks\Contracts;

interface TaskAssignee
{
    public function tasks();
    public function pendingTasks();
    public function completedTasks();
}
