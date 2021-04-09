<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\RolesAndPermissionsSeeder;

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
            // RolesAndPermissionsSeeder::class,  //Needs optimization for testing.
            UsersTableSeeder::class,
            EpTypesTableSeeder::class,
            DocumentTypesTableSeeder::class,
        ];
        if (app()->environment('testing')) {
            $seederClasses[] = CdwgsTableSeeder::class;
        }

        foreach ($seederClasses as $seederClass) {
            $seeder = new $seederClass();
            $seeder->run();
        }
    }
}
