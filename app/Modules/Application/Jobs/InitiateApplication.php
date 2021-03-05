<?php

namespace App\Modules\Application\Jobs;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;

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
        private string $cdwg_id,
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
        $application = Application::initiate(...get_object_vars($this));
    }

}
