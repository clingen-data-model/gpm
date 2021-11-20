<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Events\ParentUpdated;
use Lorisleiva\Actions\Concerns\AsController;

class ParentUpdate
{
    use AsController;

    public function handle(Group $group, Group $parent)
    {
        if ($group->parent_id == $parent->id) {
            return;
        }

        $oldParent = $group->parent;

        $group->update([
            'parent_id' => $parent->id
        ]);

        event(new ParentUpdated($group, $parent, $oldParent));

        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $parent = Group::findOrFail($request->parent_id);
        return $this->handle($group, $parent);
    }

    public function rules()
    {
        return [
            'parent_id' => 'required|exists:groups,id'
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user() && Auth::user()->can('groups-manage');
    }
}
