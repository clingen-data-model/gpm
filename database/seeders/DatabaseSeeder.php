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
        $seederClasses = [];
        $seederClasses = array_merge($seederClasses, [
            GroupRoleAndPermissionsSeeder::class,
            RolesAndPermissionsSeeder::class,
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
            CredentialSeeder::class,
            ExpertiseSeeder::class,
        ]);

        if (app()->environment('testing')) {
            array_push($seederClasses, AnnualUpdateWindowSeeder::class);
        }

        if (app()->environment('local')) {
            array_push($seederClasses, UsersTableSeeder::class);
        }

        foreach ($seederClasses as $seederClass) {
            $seeder = new $seederClass();
            $seeder->run();
        }
    }
}
