<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\GroupTypeSeeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\GroupStatusSeeder;
use Database\Seeders\DocumentTypesTableSeeder;
use Database\Seeders\GroupRoleAndPermissionsSeeder;
use Database\Seeders\NextActionAssigneesTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $seederClasses = [];
        $seederClasses = array_merge($seederClasses, [
            GroupRoleAndPermissionsSeeder::class,
            UsersTableSeeder::class,
            CdwgsTableSeeder::class,
            NextActionAssigneesTableSeeder::class,
            EpTypesTableSeeder::class,
            NextActionAssigneesTableSeeder::class,
            DocumentTypesTableSeeder::class,
            GroupTypeSeeder::class,
            GroupStatusSeeder::class
        ]);

        foreach ($seederClasses as $seederClass) {
            $seeder = new $seederClass();
            $seeder->run();
        }
    }
}
