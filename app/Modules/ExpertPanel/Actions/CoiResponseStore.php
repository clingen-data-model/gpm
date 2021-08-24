<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Http\Requests\CoiStorageRequest;
use App\Modules\ExpertPanel\Events\CoiCompleted;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use Ramsey\Uuid\Uuid;

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

        $coi = new Coi(['uuid'=>Uuid::uuid4(), 'data' => $data]);
        $coi->expert_panel_id = $expertPanel->id;
        $coi->save();

        Event::dispatch(new CoiCompleted($expertPanel, $coi));
    }

    public function asController($coiCode, CoiStorageRequest $request)
    {
        $this->handle($coiCode, $request->all());

        return response(['message' => 'COI response stored.'], 200);
    }
}
