<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\GroupVisibility;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\ClinvarUpdated;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use Illuminate\Support\Facades\Log;

class ClinvarUpdate
{
    use AsController;

    public function handle(Group $group, string $newClinvarID): Group
    {
        if ($group->clinvar_id == $newClinvarID) {
            return $group;
        }
        if($group->group_type_id != config('groups.types.vcep.id')) {
            return $group;
        }
        $oldClinvarID = $group->clinvar_id;
        $group->update(['clinvar_id' => $newClinvarID]);
        
        event(new ClinvarUpdated($group, $newClinvarID, $oldClinvarID));
        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $clinvarID = $request->clinvar_id;        
        $group = $this->handle($group, $clinvarID);
        $group->load('expertPanel');
        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'clinvar_id' => ['required', 'string', 'max:20'],
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        $user = Auth::user();
        if (! $user) { return false; }
        if ($user->hasAnyRole(['super-user', 'super-admin'])) { return true; }
        if (! $user->person) { return false; }
        
        $group = $request->route('group');
        if (! $group instanceof Group) {
            $group = Group::find($group);
        }
        if (! $group) { return false; }
        return $group->memberIsCoordinator($user->person->id);

    }

    public function getValidationMessages()
    {
        return [
            'required'  => 'ClinVar ID field is required.',
        ];
    }
}