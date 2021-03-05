<?php

namespace Database\Seeders;

use App\Modules\Application\Jobs\InitiateApplication;
use App\Modules\Application\Models\Application;
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
        foreach(Application::factory(2)->make() as $app) {
            $job = new InitiateApplication($app->uuid, $app->working_name, $app->cdwg_id, $app->ep_type_id, Carbon::now());
            \Bus::dispatch($job);
        }
    }
}
