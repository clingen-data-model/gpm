<?php

namespace Database\Seeders;

use App\Modules\Group\Models\GroupType;

class GroupTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('groups.types', GroupType::class);
    }
}
