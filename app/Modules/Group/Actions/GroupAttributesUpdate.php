<?php

namespace App\Modules\Group\Actions;

use App\Modules\ExpertPanel\Events\{
    ApplicationCompleted,
    ExpertPanelAttributesUpdated
};
use App\Modules\Group\Events\{
    GroupDescriptionUpdated,
    GroupNameUpdated,
    GroupStatusUpdated,
    ParentUpdated
};
use App\Modules\Group\Models\{
    Group,
    GroupStatus
};
use Illuminate\Support\Facades\{
    Auth,
    Event
};
use Lorisleiva\Actions\{
    Concerns\AsController,
    Concerns\AsListener,
    ActionRequest
};

class GroupAttributesUpdate
{
    use AsController;
    use AsListener;

    public function handle(
        Group $group,
        array $attributes
    ): Group {
        $attributes = collect($attributes);
        $group->fill($attributes->toArray());
        if ($group->isDirty()) {
            $updatedAttributes = $group->getDirty();
            $originalAttributes = $group->getOriginal();
            $group->save();

            if (isset($updatedAttributes['name'])) {
                if ($group->isEp) {
                    // FIXME: remove this when expertPanel just relies on group aname for long_base_name
                    $group->expertPanel()->update(['name' => $updatedAttributes['name']]);
                    $group->expertPanel()->save();
                    Event::dispatch(new ExpertPanelAttributesUpdated($group->expertPanel, ['name' => $updatedAttributes['name']]));
                }
                event(new GroupNameUpdated(group: $group, newName: $updatedAttributes['name'], oldName: $originalAttributes['name']));
            }

            if (isset($updatedAttributes['description'])) {
                event(new GroupDescriptionUpdated($group, $updatedAttributes['description'], $originalAttributes['description']));
            }

            if (isset($updatedAttributes['group_status_id'])) {
                event(
                    new GroupStatusUpdated(
                        $group,
                        GroupStatus::find($updatedAttributes['group_status_id']),
                        GroupStatus::find($originalAttributes['group_status_id'])
                    )
                );
            }

            if (isset($updatedAttributes['parent_id'])) {
                event(
                    new ParentUpdated(
                        $group,
                        Group::find($updatedAttributes['parent_id']),
                        Group::find($originalAttributes['parent_id'])
                    )
                );
            }

        }

        return $group;
    }

    public function rules()
    {
        return [
            'name' => 'string|max:255',
            'description' => 'string|nullable',
            'parent_id' => 'nullable|exists:groups,id',
            'group_status_id' => 'exists:group_statuses,id',
        ];
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $data = $request->only('name', 'description', 'parent_id', 'group_status_id');
        return $this->handle($group, $data);
    }

    public function authorize(ActionRequest $request): bool
    {
        $user = Auth::user();
        if (!$user) { return false;}

        // If only updating the description field
        $group = $request->group;
        $group->load('expertPanel');
        if ($request->has('description')) {
            if ($group->expertPanel && $group->expertPanel->getApprovalDateForStep(1)) {
                return $user->hasRole('super-admin') || $user->hasRole('super-user') || $user->hasRole('coordinator', $group);
            }
        }

        // Default permission check for other fields
        return $user->hasPermissionTo('groups-manage');
    }

    public function asListener(ApplicationCompleted $event)
    {
        $this->handle($event->application->group, ['group_status_id' => config('groups.statuses.active.id')]);
    }
}
