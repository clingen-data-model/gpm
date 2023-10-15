<?php

namespace Database\Seeders;

use App\Tasks\Models\TaskType;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('tasks.types', TaskType::class);
    }
}
