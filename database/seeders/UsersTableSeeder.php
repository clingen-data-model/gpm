<?php

namespace Database\Seeders;

use Ramsey\Uuid\Uuid;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Modules\Person\Models\Person;

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
                'name' => 'Super User',
                'email' => 'super.user@example.com',
                'role' => 'super-user'
            ],
            [
                'name' => 'Super Admin',
                'email' => 'super.admin@example.com',
                'role' => 'super-admin'
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);
            $userData['password'] = (app()->environment('local')) ? Hash::make('tester') : Hash::make(uniqid());

            $user = User::firstOrCreate(['email' => $userData['email']], $userData);
            [$firstName, $lastName] = preg_split('/ /', $user->name);
            Person::firstOrCreate(
                ['email' => $user->email],
                [
                    'uuid' => Uuid::uuid4()->toString(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'user_id' => $user->id,
                ]
            );
            if (!app()->environment('testing')) {
                $user->assignRole($role);
            }
        }
    }
}
