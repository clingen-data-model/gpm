<?php

namespace Database\Seeders;

use App\Modules\ExpertPanel\Jobs\InitiateApplication;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ApplicationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(ExpertPanel::factory(2)->make() as $app) {
            $job = new InitiateApplication($app->uuid, $app->working_name, $app->cdwg_id, $app->expert_panel_type_id, Carbon::now());
            \Bus::dispatch($job);
        }
    }
}
