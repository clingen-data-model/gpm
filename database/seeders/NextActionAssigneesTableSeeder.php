<?php

namespace Database\Seeders;

use App\Modules\ExpertPanel\Models\NextActionAssignee;

class NextActionAssigneesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('next_actions.assignees', NextActionAssignee::class);
    }
}
