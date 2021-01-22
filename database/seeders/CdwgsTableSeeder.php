<?php

namespace Database\Seeders;

use App\Models\Cdwg;
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
        Cdwg::factory(2)->create();
    }
}
