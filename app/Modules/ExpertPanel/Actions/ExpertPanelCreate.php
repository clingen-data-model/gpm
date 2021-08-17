<?php

namespace App\Modules\ExpertPanel\Actions;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;
use App\Modules\ExpertPanel\Http\Requests\InitiateApplicationRequest;

class ExpertPanelCreate
{
    use AsAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(
        string $uuid,
        string $working_name,
        int|null $cdwg_id,
        int $ep_type_id,
        ?DateTime $date_initiated = null,
    )
    {
        if (is_null($date_initiated)) {
            $date_initiated = Carbon::now();
        }

        $expertPanel = new ExpertPanel();
        $expertPanel->uuid = $uuid;
        $expertPanel->working_name = $working_name;
        $expertPanel->cdwg_id = $cdwg_id;
        $expertPanel->ep_type_id = $ep_type_id;
        $expertPanel->date_initiated = $date_initiated;
        $expertPanel->coi_code = bin2hex(random_bytes(12));
        $expertPanel->current_step = 1;

        $expertPanel->save();
    
        Event::dispatch(new ApplicationInitiated($expertPanel));


        return $expertPanel;
    }

    public function asController(InitiateApplicationRequest $request)
    {
        $data = $request->except('contacts');
        $data['cdwg_id'] = $request->cdwg_id;
        $data['date_initiated'] = $request->date_initiated ? Carbon::parse($request->date_initiated) : null;
        $expertPanel = $this->handle(...$data);
        return response($expertPanel, 200);
    }
    

}
