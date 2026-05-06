<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FundingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('funding_types')->upsert(
            [
                ['id' => 1, 'name' => 'Industry',   'created_at' => $now, 'updated_at' => $now],
                ['id' => 2, 'name' => 'Foundation', 'created_at' => $now, 'updated_at' => $now],
                ['id' => 3, 'name' => 'Advocacy',   'created_at' => $now, 'updated_at' => $now],
                ['id' => 4, 'name' => 'Government', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['name'],
            ['updated_at']
        );
    }
}