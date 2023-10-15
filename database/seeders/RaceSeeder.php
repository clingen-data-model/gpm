<?php

namespace Database\Seeders;

use App\Modules\Person\Models\Race;

class RaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['id' => 1, 'name' => 'American Indian or Alaska Native'],
            ['id' => 2, 'name' => 'Asian'],
            ['id' => 3, 'name' => 'Black or African American'],
            ['id' => 4, 'name' => 'Native Hawaiian or Other Pacific Islander'],
            ['id' => 5, 'name' => 'White'],
            ['id' => 6, 'name' => 'Prefer not to answer'],
            ['id' => 100, 'name' => 'I would prefer to describe myself as the following'],
        ];

        $this->seedFromArray($items, Race::class);
    }
}
