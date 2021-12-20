<?php

namespace Database\Seeders;

use App\Modules\ExpertPanel\Models\RulesetStatus;
use Database\Seeders\Seeder;

class RulesetStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('specifications.rulesets.statuses', RulesetStatus::class);
    }
}
