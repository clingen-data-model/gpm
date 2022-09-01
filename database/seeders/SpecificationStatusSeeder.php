<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Modules\ExpertPanel\Models\RulesetStatus;
use App\Modules\ExpertPanel\Models\SpecificationStatus;

class SpecificationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('specifications.statuses', SpecificationStatus::class);
    }
}
