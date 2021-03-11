<?php

namespace App\Modules\Application\Jobs;

use App\Models\Coi;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\CoiCompleted;

class StoreCoiResponse
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private String $coiCode, private Array $responseData)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $application = Application::findByCoiCodeOrFail($this->coiCode);
        $coi = new Coi(['data' => $this->responseData]);
        $coi->application_id = $application->id;
        $coi->save();

        Event::dispatch(new CoiCompleted($application, $coi));
    }
}
