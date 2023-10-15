<?php

namespace Database\Seeders;

use App\Modules\Group\Models\GroupStatus;

class GroupStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->seedFromConfig('groups.statuses', GroupStatus::class);
    }
}
