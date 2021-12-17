<?php

namespace App\Modules\ExpertPanel\Actions;

use DateTime;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Group\Actions\GroupCreate;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;
use App\Modules\ExpertPanel\Http\Requests\InitiateApplicationRequest;

class ExpertPanelCreate
{
    use AsAction;

    public function __construct(GroupCreate $createGroup)
    {
        $this->createGroup = $createGroup;
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(
        string $uuid,
        string $working_name,
        int|null $cdwg_id,
        int $expert_panel_type_id,
        ?DateTime $date_initiated = null,
    ) {
        if (is_null($date_initiated)) {
            $date_initiated = Carbon::now();
        }
        
        $group = $this->createGroup->handle([
            'uuid' => $uuid,
            'name' => $working_name,
            'group_type_id' => config('groups.types.ep.id'),
            'group_status_id' => config('groups.statuses.pending-approval.id'),
            'parent_id' => $cdwg_id
        ]);

        $expertPanel = new ExpertPanel();
        $expertPanel->uuid = $uuid;
        $expertPanel->group_id = $group->id;
        $expertPanel->expert_panel_type_id = $expert_panel_type_id;
        $expertPanel->date_initiated = $date_initiated;
        $expertPanel->coi_code = bin2hex(random_bytes(12));
        $expertPanel->cdwg_id = $cdwg_id;
        $expertPanel->current_step = 1;

        $group->expertPanel()->save($expertPanel);
    
        Event::dispatch(new ApplicationInitiated($expertPanel));


        return $group;
    }

    public function asController(InitiateApplicationRequest $request)
    {
        $data = $request->except('contacts');
        $data['cdwg_id'] = $request->cdwg_id;
        $data['date_initiated'] = $request->date_initiated ? Carbon::parse($request->date_initiated) : null;
        $group = $this->handle(...$data);
        $group->load('expertPanel');
        return response($group, 200);
    }
}
