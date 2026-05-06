<?php

namespace Database\Seeders;

use App\Modules\Funding\Models\FundingType;

class FundingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFromConfig('funding.types', FundingType::class);
    }
}
