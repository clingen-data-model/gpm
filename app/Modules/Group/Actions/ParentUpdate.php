<?php
namespace App\Modules\Group\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Events\ParentUpdated;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Actions\AffiliationUpdate;
use Illuminate\Support\Facades\Log;

class ParentUpdate
{
    use AsController;

    public function __construct(
        private AffiliationUpdate $affiliationUpdate
    ) {
    }

    public function handle(Group $group, ?Group $parent)
    {
        if ($group->parent_id == $parent->id) {
            return $group;
        }

        $oldParent = $group->parent;

        $parentId = is_null($parent) ? null : $parent->id;

        $group->update([
            'parent_id' => $parentId
        ]);

        event(new ParentUpdated($group, $parent, $oldParent));

        if ((int) $group->expertPanel->affiliation_id > 0) {
            try {
                $this->affiliationUpdate->handle($group->expertPanel);
            } catch (\Throwable $e) {
                Log::warning('AM sync on status change failed', [
                    'group_uuid'        => $group->uuid,
                    'expert_panel_uuid' => (string) $group->expertPanel->uuid,
                    'oldParent'         => $oldParent?->name ?? null,
                    'newParent'         => $parent?->name ?? null,
                    'message'           => $e->getMessage(),
                    'code'              => $e->getCode(),
                ]);
            }
        }
        
        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $parent = Group::find($request->parent_id) ?? new Group(['name' => 'none']);
        return $this->handle($group, $parent);
    }

    public function rules()
    {
        return [
            'parent_id' => [
                            'required',
                            function ($attribute, $value, $fail) {
                                if ($value != 0) {
                                    if (!DB::table('groups')->where('id', $value)->exists()) {
                                        $fail('The selected parent is not valid.');
                                    }
                                }
                            }
                        ]
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user() && Auth::user()->can('groups-manage');
    }
}
