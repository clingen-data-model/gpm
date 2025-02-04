<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Events\GroupDescriptionUpdated;

class GroupDescriptionUpdate
{
    use AsAction;

    public function handle(Group $group, ?string $description): Group
    {
        if ($group->description == $description) {
            return $group;
        }

        $group->update([
            'description' => $description
        ]);

        event(new GroupDescriptionUpdated($group, $description));

        return $group;
    }
    
    public function asController(ActionRequest $request, $uuid)
    {
        $group = Group::findByUuidOrFail($uuid);
        if (Auth()->user()->cannot('updateApplicationAttribute', $group)) {
            throw new AuthorizationException('You do not have permission to update this Expert Panel.');
        }
        $group = $this->handle($group, $request->description);
        $group->load('expertPanel', 'members', 'members.person');

        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'description' => 'required|max:66535'
        ];
    }

    public function getValidationMessages(): array
    {
        return ['required' => 'This field is required.'];
    }
}
