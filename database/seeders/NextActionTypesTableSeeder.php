<?php

namespace Database\Seeders;

use App\Models\NextActionType;

class NextActionTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('next_actions.types', NextActionType::class);
    }
}
