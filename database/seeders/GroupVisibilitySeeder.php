<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupVisibilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('group_visibilities')->upsert(
            [
                [
                    'id'            => 1,
                    'name'          => 'public',
                    'description'   => 'Visible to non-admin users and on the ClinGen website.',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
                [
                    'id'            => 2,
                    'name'          => 'private',
                    'description'   => 'Hidden from non-admin users and from the ClinGen website.',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
            ],
            ['name'], // unique key
            ['description', 'updated_at']
        );
    }
}
