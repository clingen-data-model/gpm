<?php
namespace App\Modules\Group\Actions;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Actions\CoiCodeMake;
use App\Modules\Group\Events\GroupCreated;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Http\Resources\GroupResource;

class GroupCreate
{
    use AsController;

    public function __construct(private CoiCodeMake $makeCoiCode)
    {
    }


    public function handle($data): Group
    {
        $group = Group::create([
            'uuid' => isset($data['uuid']) ? $data['uuid'] : Uuid::uuid4(),
            'name' => $data['name'],
            'group_type_id' => $data['group_type_id'],
            'group_status_id' => $data['group_status_id'],
            'coi_code' => $this->makeCoiCode->handle(),
            'parent_id' => $this->resolveParentId($data),
        ]);

        if ($group->isEp) {
            $expertPanel = new ExpertPanel([
                'long_base_name' => $data['name'],
                'short_base_name' => isset($data['short_base_name']) ? $data['short_base_name'] : null,
                'group_id' => $group->id,
                'cdwg_id' => $this->resolveParentId($data),
                'expert_panel_type_id' => ($data['group_type_id'] - 2),
                'date_initiated' => Carbon::now(),
                'current_step' => 1,
            ]);
            $expertPanel->uuid = Uuid::uuid4();
            $group->expertPanel()->save($expertPanel);
        }

        event(new GroupCreated($group));

        return $group;
    }

    public function asController(ActionRequest $request)
    {
        $data = $request->only('name', 'group_type_id', 'group_status_id', 'parent_id', 'long_base_name', 'short_base_name');

        $group = $this->handle($data);
        $group->load('expertPanel');

        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'long_base_name' => 'max:255',
            'short_base_name' => 'max:16',
            'group_type_id' => 'required', // TODO: should check for existence when we merge vcep/gcep into group
            'group_status_id' => 'required|exists:group_statuses,id',
            'parent_id' => [
                'nullable',
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
        return Auth::user() && Auth::user()->can('create', Group::class);
    }

    public function getValidationMessages()
    {
        return [
            'required' => 'This field is required.',
            'exists' => 'The selection is invalid.'
        ];
    }

    private function resolveParentId($data): ?int
    {
        return isset($data['parent_id']) && $data['parent_id'] > 0 ? $data['parent_id'] : null;
    }
}
