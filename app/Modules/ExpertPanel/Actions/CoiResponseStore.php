<?php

namespace App\Modules\ExpertPanel\Actions;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Event;
use App\Modules\ExpertPanel\Models\Coi;
use App\Http\Requests\CoiStorageRequest;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\CoiCompleted;

class CoiResponseStore
{
    use AsAction;

    public function handle(String $coiCode, ?int $groupMemberId, array $responseData)
    {
        $expertPanel = ExpertPanel::findByCoiCodeOrFail($coiCode);
        $groupMember = GroupMember::find($groupMemberId);

        $data = $responseData;
        if ($groupMember) {
            $data['first_name'] = $groupMember->person->first_name;
            $data['last_name'] = $groupMember->person->last_name;
            $data['email'] = $groupMember->person->email;
        }

        if (in_array('document_uuid', array_keys($responseData))) {
            $data['email'] = 'Legacy Coi';
            $data['first_name'] = 'Legacy';
            $data['last_name'] = 'Coi';
        }
        
        $coi = Coi::create([
            'uuid'=>Uuid::uuid4()->toString(),
            'group_member_id' => $groupMemberId,
            'data' => $data,
            'expert_panel_id' => $expertPanel->id,
            'completed_at' => Carbon::now()
        ]);

        Event::dispatch(new CoiCompleted($expertPanel, $coi));

        return $coi;
    }

    public function asController($coiCode, CoiStorageRequest $request)
    {
        $coi = $this->handle($coiCode, $request->group_member_id, $request->except('group_member_id'));
        return response(['message' => 'COI response stored.'], 200);
    }
}
