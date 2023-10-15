<?php

namespace Database\Seeders;

use App\Tasks\Models\TaskType;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->seedFromConfig('tasks.types', TaskType::class);
    }
}
