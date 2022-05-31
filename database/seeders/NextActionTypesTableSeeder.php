<?php

namespace Database\Seeders;

use App\Models\NextActionType;
use App\Modules\ExpertPanel\Models\NextActionAssignee;

class NextActionTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('next_actions.types', NextActionType::class);
    }
}
