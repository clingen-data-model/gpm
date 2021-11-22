<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\GroupNameUpdated;
use App\Modules\Group\Http\Resources\GroupResource;

class GroupNameUpdate
{
    use AsController;

    public function handle(Group $group, String $name): Group
    {
        if ($name == $group->name) {
            return $group;
        }

        $oldName = $group->name;
        $group->update(['name' => $name]);

        event(new GroupNameUpdated(group: $group, newName: $name, oldName: $oldName));

        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $group = $this->handle($group, $request->name);
        $group->load('expertPanel');
        return new GroupResource($group);
    }

    public function rules()
    {
        return [
            'name' => 'required|max:255'
        ];
    }
    
    public function authorize(ActionRequest $request): bool
    {
        return Auth::user() && Auth::user()->hasPermissionTo('groups-manage');
    }
}
