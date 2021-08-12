<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\Coi;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\CoiCompleted;

class StoreCoiResponse
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private String $coiCode, private array $responseData)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $expertPanel = ExpertPanel::findByCoiCodeOrFail($this->coiCode);

        $data = $this->responseData;
        if (in_array('document_uuid', array_keys($this->responseData))) {
            $data['email'] = 'Legacy Coi';
            $data['first_name'] = 'Legacy';
            $data['last_name'] = 'Coi';
        }

        $coi = new Coi(['data' => $data]);
        $coi->application_id = $expertPanel->id;
        $coi->save();

        Event::dispatch(new CoiCompleted($expertPanel, $coi));
    }
}
