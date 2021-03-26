<?php

namespace Database\Seeders;

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'tj ward',
                'email' => 'jward3@email.unc.edu',
                'password' => Hash::make(uniqid()),
            ],
            [
                'name' => 'Danielle Azaritti',
                'email' => 'dazzarit@broadinstitute.org',
                'password' => Hash::make(uniqid()),
            ],
            [
                'name' => 'Hanna Dziadzio',
                'email' => 'hdziadzi@broadinstitute.org',
                'password' => Hash::make(uniqid()),
            ],
            [
                'name' => 'Emma Owens',
                'email' => 'emma_owens@med.unc.edu',
                'password' => Hash::make(uniqid()),
            ]

        ];

        foreach ($users as $userData ) {
            User::create($userData);
        }

    }
}
