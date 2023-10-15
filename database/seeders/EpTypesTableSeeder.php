<?php

namespace Database\Seeders;

use App\Modules\ExpertPanel\Models\ExpertPanelType;

class EpTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('expert_panels.types', ExpertPanelType::class);
    }
}
