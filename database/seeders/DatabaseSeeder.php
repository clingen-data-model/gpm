<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $seederClasses = [
            EpTypesTableSeeder::class,
        ];

        foreach ($seederClasses as $seederClass) {
            $seeder = new $seederClass();
            $seeder->run();
            echo $seederClass." complete\n";
        }
    }
}
