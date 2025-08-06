<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Events\ScopeDescriptionUpdated;

class ScopeDescriptionUpdate
{
    use AsAction;

    public function handle(Group $group, ?string $description): Group
    {
        if (!$group->isExpertPanel) {
            throw ValidationException::withMessages(['scope_description' => ['A description of scope can only be set for expert panels.']]);
        }

        if ($group->expertPanel->scope_description == $description) {
            return $group;
        }

        $group->expertPanel->update([
            'scope_description' => $description
        ]);

        event(new ScopeDescriptionUpdated($group, $description));

        return $group;
    }
    
    public function asController(ActionRequest $request, $uuid)
    {
        $user = Auth::user();
        $group = Group::findByUuidOrFail($uuid);
        $group->load('expertPanel', 'members', 'members.person');

        // Step 1 approved → only super-admin
        if ($group->expertPanel && $group->expertPanel->getApprovalDateForStep(1)) {
            if (!$user->hasRole('super-admin')) {
                throw new AuthorizationException('Only super-admin can update the scope description after approval.');
            }
        } else {
            if ($user->cannot('updateApplicationAttribute', $group)) {
                throw new AuthorizationException('You do not have permission to update this Expert Panel.');
            }
        }
        $group = $this->handle($group, $request->scope_description);
        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'scope_description' => 'required|max:66535'
        ];
    }

    public function getValidationMessages(): array
    {
        return ['required' => 'This field is required.'];
    }
}
