<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\GroupNameUpdated;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\ExpertPanel\Service\AffilsClient;
use Illuminate\Support\Facades\Log;

class GroupNameUpdate
{
    use AsController;

    public function __construct(private AffilsClient $affils) {}

    public function handle(Group $group, String $name): Group
    {
        if ($name == $group->name) {
            return $group;
        }

        $oldName = $group->name;
        $group->update(['name' => $name]);

        if ($group->group_type_id == 2 && $group->parent_id > 0) {
            try {
                $this->affils->updateCDWG($group->parent_id, ['name' => $name]);
            } catch (\RuntimeException $e) {                
                Log::warning('Affils CDWG name update failed', [
                    'group_id' => $group->id,
                    'ext_id'   => $group->parent_id,
                    'message'  => $e->getMessage(),
                    'status'   => $e->getCode(),
                ]);

                $group->update(['name' => $oldName]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => [$e->getMessage()],
                ]);
            }
        }

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
