<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ExpertiseSeeder;
use Database\Seeders\GroupTypeSeeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\GroupStatusSeeder;
use Database\Seeders\InstitutionSeeder;
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
            CommentTypesSeeder::class,
            CountrySeeder::class,
            CredentialSeeder::class,
            CurationReviewProtocolsSeeder::class,
            DocumentTypesTableSeeder::class,
            ExpertiseSeeder::class,
            EpTypesTableSeeder::class,
            GroupRoleAndPermissionsSeeder::class,
            GroupStatusSeeder::class,
            GroupTypeSeeder::class,
            InstitutionSeeder::class,
            NextActionAssigneesTableSeeder::class,
            NextActionTypesTableSeeder::class,
            RolesAndPermissionsSeeder::class,
            SubmissionTypeAndStatusSeeder::class,
            TaskTypeSeeder::class,
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
