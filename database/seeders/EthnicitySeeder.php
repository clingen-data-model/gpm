<?php

namespace Database\Seeders;

use App\Modules\Person\Models\Ethnicity;
use Database\Seeders\Seeder;

class EthnicitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            ['id' => 1, 'name' => 'Hispanic or Latino'],
            ['id' => 2, 'name' => 'Not Hispanic or Latino'],
            ['id' => 3, 'name' => 'Unknown'],
            ['id' => 4, 'name' => 'Prefer Not to Answer'],
        ];

        $this->seedFromArray($items, Ethnicity::class);
    }
}
