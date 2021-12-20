<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\GroupStatus;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\GroupStatusUpdated;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;

class GroupStatusUpdate
{
    use AsController;
    use AsListener;

    public function handle(Group $group, GroupStatus $groupStatus): Group
    {
        if ($group->group_status_id == $groupStatus->id) {
            return $group;
        }

        $oldStatus = $group->status;

        $group->update(['group_status_id' => $groupStatus->id]);

        event(new GroupStatusUpdated($group, $groupStatus, $oldStatus));

        return $group;
    }

    public function asListener(ApplicationCompleted $event)
    {
        $activeStatus = GroupStatus::find(config('groups.statuses.active.id'));
        $this->handle($event->application->group, $activeStatus);
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $newStatus = GroupStatus::findOrFail($request->status_id);
        $group = $this->handle($group, $newStatus);
        $group->load('expertPanel');
        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'status_id' => 'required|exists:group_statuses,id',
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user() && Auth::user()->hasPermissionTo('groups-manage');
    }

    public function getValidationMessages()
    {
        return [
            'required' => 'This field is required.',
            'exists' => 'The status you selected is invalid.'
        ];
    }
}
