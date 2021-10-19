<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Modules\Person\Models\Gender;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            ['id' => 1, 'name' => 'Female'],
            ['id' => 2, 'name' => 'Male'],
            ['id' => 3, 'name' => 'Transgender Female'],
            ['id' => 4, 'name' => 'Transgender Male'],
            ['id' => 5, 'name' => 'Gender Variant/Non-conforming'],
            ['id' => 6, 'name' => 'Prefer not to answer'],
            ['id' => 100, 'name' => 'I would prefer to describe myself as the following'],
        ];

        $this->seedFromArray($items, Gender::class);
    }
}
