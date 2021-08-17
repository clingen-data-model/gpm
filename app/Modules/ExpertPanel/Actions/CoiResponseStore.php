<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\Coi;
use Illuminate\Support\Facades\Event;
use App\Http\Requests\CoiStorageRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\CoiCompleted;

class CoiResponseStore
{
    use AsAction;

    public function handle(String $coiCode, array $responseData)
    {
        $expertPanel = ExpertPanel::findByCoiCodeOrFail($coiCode);

        $data = $responseData;
        if (in_array('document_uuid', array_keys($responseData))) {
            $data['email'] = 'Legacy Coi';
            $data['first_name'] = 'Legacy';
            $data['last_name'] = 'Coi';
        }

        $coi = new Coi(['data' => $data]);
        $coi->application_id = $expertPanel->id;
        $coi->save();

        Event::dispatch(new CoiCompleted($expertPanel, $coi));
    }

    public function asController($coiCode, CoiStorageRequest $request)
    {
        $this->handle($coiCode, $request->all());

        return response(['message' => 'COI response stored.'], 200);
    }
    
}
