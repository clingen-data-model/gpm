<?php

namespace Database\Seeders;

use App\Modules\Person\Models\Country;

class NewCountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $newCountries = [
            ['id' => 241, 'name' => 'State of Palestine'],
        ];

        $this->seedFromArray($newCountries, Country::class);
    }
}
