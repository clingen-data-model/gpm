<?php

namespace Database\Seeders;

use App\Models\EpType;

class EpTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('expert_panels.types', EpType::class);
    }
}
