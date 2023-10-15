<?php

namespace Database\Seeders;

use App\Modules\ExpertPanel\Models\NextActionAssignee;

class NextActionAssigneesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('next_actions.assignees', NextActionAssignee::class);
    }
}
