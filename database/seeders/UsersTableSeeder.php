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
                'role' => 'super-user'
            ],
            [
                'name' => 'Danielle Azzariti',
                'email' => 'dazzarit@broadinstitute.org',
                'password' => Hash::make(uniqid()),
                'role' => 'admin'
            ],
            [
                'name' => 'Hanna Dziadzio',
                'email' => 'hdziadzi@broadinstitute.org',
                'password' => Hash::make(uniqid()),
                'role' => 'admin'
            ],
            [
                'name' => 'Emma Owens',
                'email' => 'emma_owens@med.unc.edu',
                'password' => Hash::make(uniqid()),
                'role' => 'admin'
            ],
            [
                'name' => 'Courtney Thaxton',
                'email' => 'courtney_thaxton@med.unc.edu',
                'password' => Hash::make(uniqid()),
                'role' => 'admin'
            ],
            [
                'name' => 'Laura Milko',
                'email' => 'laura_milko@med.unc.edu',
                'password' => Hash::make(uniqid()),
                'role' => 'admin'
            ]

        ];

        // foreach ($users as $userData ) {

        //     $role = $userData['role'];
        //     unset($userData['role']); 
        //     $user = User::create($userData);
        //     $user->assignRole($role);
        // }

    }
}
