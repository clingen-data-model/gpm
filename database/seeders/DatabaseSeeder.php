<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\ExpertPanel\Models\NextActionAssignee;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\DocumentTypesTableSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
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
        if (!app()->environment('testing')) {
            $seederClasses[] = RolesAndPermissionsSeeder::class; // Needs optimization for refreshing testing db
        }
            
        $seederClasses = array_merge($seederClasses, [
            UsersTableSeeder::class,
            CdwgsTableSeeder::class,
            NextActionAssigneesTableSeeder::class,
            EpTypesTableSeeder::class,
            NextActionAssigneesTableSeeder::class,
            DocumentTypesTableSeeder::class,
        ]);

        // if (app()->environment('testing')) {
        $seederClasses[] = CdwgsTableSeeder::class;
        // }

        foreach ($seederClasses as $seederClass) {
            $seeder = new $seederClass();
            $seeder->run();
        }
    }
}
