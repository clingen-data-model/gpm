<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CdwgsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Cdwg::class, 10)->make([]);
    }
}
