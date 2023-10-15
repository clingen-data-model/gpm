<?php

namespace Database\Seeders;

use App\Modules\Person\Models\Ethnicity;

class EthnicitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
