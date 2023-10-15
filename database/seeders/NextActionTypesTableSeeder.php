<?php

namespace Database\Seeders;

use App\Models\NextActionType;

class NextActionTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->seedFromConfig('next_actions.types', NextActionType::class);
    }
}
