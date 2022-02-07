<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Tasks\Models\TaskType;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('tasks.types', TaskType::class);
    }
}
