<?php

namespace Database\Seeders;

use App\Modules\ExpertPanel\Models\CurationReviewProtocol;

class CurationReviewProtocolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('expert_panels.curation_protocols', CurationReviewProtocol::class);
    }
}
