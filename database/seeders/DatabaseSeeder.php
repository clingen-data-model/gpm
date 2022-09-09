<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\GroupTypeSeeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\GroupStatusSeeder;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\DocumentTypesTableSeeder;
use Database\Seeders\SpecificationStatusSeeder;
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
            RolesAndPermissionsSeeder::class,
            // UsersTableSeeder::class,
            CdwgsTableSeeder::class,
            NextActionAssigneesTableSeeder::class,
            EpTypesTableSeeder::class,
            NextActionAssigneesTableSeeder::class,
            NextActionTypesTableSeeder::class,
            DocumentTypesTableSeeder::class,
            GroupTypeSeeder::class,
            GroupStatusSeeder::class,
            PrimaryOccupationSeeder::class,
            EthnicitySeeder::class,
            RaceSeeder::class,
            GenderSeeder::class,
            CountrySeeder::class,
            InstitutionSeeder::class,
            CurationReviewProtocolsSeeder::class,
            SubmissionTypeAndStatusSeeder::class,
            TaskTypeSeeder::class,
            CommentTypesSeeder::class,
        ]);

        if (app()->environment('testing')) {
            array_push($seederClasses, AnnualUpdateWindowSeeder::class);
        }

        foreach ($seederClasses as $seederClass) {
            $seeder = new $seederClass();
            $seeder->run();
        }
    }
}
