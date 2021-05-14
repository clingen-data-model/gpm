<?php

namespace App\Modules\Application\Jobs;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\ApplicationInitiated;

class InitiateApplication
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $uuid,
        private string $working_name,
        private int|null $cdwg_id,
        private int $ep_type_id,
        private ?DateTime $date_initiated = null,
    )
    {
        if (is_null($this->date_initiated)) {
            $this->date_initiated = Carbon::now();
        }

    }

    public function handle(): void
    {
        $application = new Application();
        $application->uuid = $this->uuid;
        $application->working_name = $this->working_name;
        $application->cdwg_id = $this->cdwg_id;
        $application->ep_type_id = $this->ep_type_id;
        $application->date_initiated = $this->date_initiated;
        $application->coi_code = bin2hex(random_bytes(12));

        $application->save();
    
        Event::dispatch(new ApplicationInitiated($application));


        // $application = Application::initiate(...get_object_vars($this));
    }

}
