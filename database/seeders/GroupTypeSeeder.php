<?php

namespace Database\Seeders;

use App\Modules\Group\Models\GroupType;

class GroupTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('groups.types', GroupType::class);
    }
}
