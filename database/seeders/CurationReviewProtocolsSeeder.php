<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Modules\ExpertPanel\Models\CurationReviewProtocol;

class CurationReviewProtocolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('expert_panels.curation_protocols', CurationReviewProtocol::class);
    }
}
