<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\GroupNameUpdated;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Actions\ScopeOfWorkRevisionGuard;

class GroupNameUpdate
{
    use AsController;

    public function __construct(
        private ScopeOfWorkRevisionGuard $scopeOfWorkRevisionGuard,
    ) {
    }

    public function handle(Group $group, String $name): Group
    {
        $this->scopeOfWorkRevisionGuard->ensureNotUnderReview($group);
        if ($name == $group->name) { return $group; }

        $oldName = $group->name;
        $group->update(['name' => $name]);

        event(new GroupNameUpdated(group: $group, newName: $name, oldName: $oldName));

        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $group = $this->handle($group, $request->name);
        $group->load('expertPanel', 'members', 'members.person');
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
