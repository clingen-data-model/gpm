<?php

namespace Database\Seeders;

use App\Modules\Person\Models\PrimaryOccupation;
use Database\Seeders\Seeder;

class PrimaryOccupationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            ['id' => 1, 'name' => 'Clinical Laboratory Director'],
            ['id' => 2, 'name' => 'Clinical Laboratory Staff'],
            ['id' => 3, 'name' => 'Research Laboratory Director'],
            ['id' => 4, 'name' => 'Research Laboratory Staff'],
            ['id' => 5, 'name' => 'Variant Scientist'],
            ['id' => 6, 'name' => 'Genetic Counselor'],
            ['id' => 7, 'name' => 'Medical Genetics Physician'],
            ['id' => 8, 'name' => 'Non-Geneticist Physician'],
            ['id' => 9, 'name' => 'Software Engineer/Developer'],
            ['id' => 10, 'name' => 'Bioinformatician'],
            ['id' => 11, 'name' => 'Medical Student/Graduate Student/Trainee/Fellow'],
            ['id' => 12, 'name' => 'Undergraduate Student'],
            ['id' => 13, 'name' => 'High School Student'],
            ['id' => 100, 'name' => 'Other']
        ];

        $this->seedFromArray($items, PrimaryOccupation::class);
    }
}
