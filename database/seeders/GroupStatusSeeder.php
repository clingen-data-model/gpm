<?php

namespace Database\Seeders;

use App\Modules\Group\Models\GroupStatus;

class GroupStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('groups.statuses', GroupStatus::class);
    }
}
