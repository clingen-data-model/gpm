<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\GroupVisibility;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\GroupVisibilityUpdated;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use Illuminate\Support\Facades\Log;

class GroupVisibilityUpdate
{
    use AsController;
    use AsListener;

    public function handle(Group $group, GroupVisibility $newGroupVisibility): Group
    {
        if ($group->group_visibility_id == $newGroupVisibility->id) {
            return $group;
        }
        if($group->group_type_id != config('groups.types.wg.id')) {
            return $group;
        }
        $oldGroupVisibility = GroupVisibility::find($group->group_visibility_id);
        $group->update(['group_visibility_id' => $newGroupVisibility->id]);

        event(new GroupVisibilityUpdated($group, $newGroupVisibility, $oldGroupVisibility));
        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $newVisibility = GroupVisibility::findOrFail($request->visibility_id);
        $group = $this->handle($group, $newVisibility);
        $group->load('expertPanel');
        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'visibility_id' => 'required|exists:group_visibilities,id',
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        $user = Auth::user();
        if (! $user) { return false; }
        return $user->hasAnyRole(['super-user', 'super-admin', 'admin']);
    }

    public function getValidationMessages()
    {
        return [
            'required'  => 'This field is required.',
            'exists'    => 'The group\'s visibility you selected is invalid.'
        ];
    }
}
