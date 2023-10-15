<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\ApplicationInitiated;
use App\Modules\ExpertPanel\Http\Requests\InitiateApplicationRequest;
use App\Modules\Group\Actions\CoiCodeMake;
use App\Modules\Group\Actions\GroupCreate;
use App\Modules\Group\Models\Group;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class ExpertPanelCreate
{
    use AsAction;

    public function __construct(private GroupCreate $createGroup, private CoiCodeMake $makeCoiCode)
    {
    }

    /**
     * Create a new job instance.
     */
    public function handle(
        string $uuid,
        string $working_name,
        ?int $cdwg_id,
        int $expert_panel_type_id,
        DateTime $date_initiated = null,
    ): void {
        if (is_null($date_initiated)) {
            $date_initiated = Carbon::now();
        }

        $groupTypeId = $expert_panel_type_id + 2;

        $group = $this->createGroup->handle([
            'uuid' => $uuid,
            'name' => $working_name,
            'group_type_id' => $groupTypeId,
            'group_status_id' => config('groups.statuses.applying.id'),
            'parent_id' => $cdwg_id,
        ]);

        // EP is created with group when group type is EP.
        $group->expertPanel->uuid = $uuid;
        $group->expertPanel->group_id = $group->id;
        $group->expertPanel->expert_panel_type_id = $expert_panel_type_id;
        $group->expertPanel->date_initiated = $date_initiated;
        $group->expertPanel->cdwg_id = $cdwg_id;
        $group->expertPanel->current_step = 1;

        $group->expertPanel->save();

        Event::dispatch(new ApplicationInitiated($group->expertPanel));

        return $group;
    }

    public function asController(InitiateApplicationRequest $request)
    {
        $data = $request->except('contacts');
        $data['cdwg_id'] = $request->cdwg_id;
        $data['date_initiated'] = $request->date_initiated ? Carbon::parse($request->date_initiated) : null;
        $group = $this->handle(...$data);

        return response($group, 200);
    }
}
