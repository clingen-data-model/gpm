<?php

namespace Database\Seeders;

use App\Models\Credential;
use Database\Seeders\Seeder;

class CredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $creds = [
            [
                'id' => 1,
                'name' => 'CGC',
                'approved' => 1,
            ],
            [
                'id' => 2,
                'name' => 'MD',
                'approved' => 1,
            ],
            [
                'id' => 3,
                'name' => 'MSc',
                'approved' => 1,
            ],
            [
                'id' => 4,
                'name' => 'PhD',
                'approved' => 1
            ]
        ];

        $this->seedFromArray($creds, Credential::class);
    }
}
