<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Modules\Group\Models\GroupStatus;

class GroupStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromSimpleArray(config('groups.statuses'), GroupStatus::class);
    }
}
