<?php

namespace App\Modules\ExpertPanel\Jobs;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;

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
        $expertPanel = new ExpertPanel();
        $expertPanel->uuid = $this->uuid;
        $expertPanel->working_name = $this->working_name;
        $expertPanel->cdwg_id = $this->cdwg_id;
        $expertPanel->ep_type_id = $this->ep_type_id;
        $expertPanel->date_initiated = $this->date_initiated;
        $expertPanel->coi_code = bin2hex(random_bytes(12));

        $expertPanel->save();
    
        Event::dispatch(new ApplicationInitiated($expertPanel));


        // $expertPanel = ExpertPanel::initiate(...get_object_vars($this));
    }

}
