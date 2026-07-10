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
use App\Modules\Group\Actions\WorkingGroupAffiliationIdGenerate;
use Illuminate\Validation\Rule;

class GroupCreate
{
    use AsController;

    public function __construct(private CoiCodeMake $makeCoiCode, private WorkingGroupAffiliationIdGenerate $wgAffiliationIdGenerator)
    {
    }


    public function handle($data): Group
    {
        $groupVisibilityId = $data['group_visibility_id'] ?? config('groups.visibilities.public.id');

        if ($groupVisibilityId === null) {
            throw new \RuntimeException('Default group visibility is not configured. Set groups.visibilities.public.id.');
        }

        $groupForTypeCheck = new Group(['group_type_id' => $data['group_type_id']]);
        $affiliationId = $data['affiliation_id'] ?? null;
        if ($groupForTypeCheck->isWorkingGroupType && ! Auth::user()?->hasRole('super-user')) {
            $affiliationId = null;
        }

        $uuid = isset($data['uuid']) ? $data['uuid'] : Uuid::uuid4();
        $group = Group::create([
            'uuid' => $uuid,
            'affiliation_id' => $affiliationId,
            'name' => $data['name'],
            'group_type_id' => $data['group_type_id'],
            'group_status_id' => $data['group_status_id'],
            'group_visibility_id' => $groupVisibilityId,
            'coi_code' => $this->makeCoiCode->handle(),
            'parent_id' => $this->resolveParentId($data),
            'website_url' => $data['website_url'] ?? null,
        ]);

        if ($group->isWorkingGroupType && !$group->affiliation_id) {
            $this->wgAffiliationIdGenerator->handle($group);
        }

        if ($group->isEp) {
            $expertPanel = new ExpertPanel([
                'long_base_name' => $data['name'],
                'short_base_name' => isset($data['short_base_name']) ? $data['short_base_name'] : null,
                'group_id' => $group->id,
                // 'cdwg_id' => $this->resolveParentId($data), DEPRECATED CGSP-1023
                'expert_panel_type_id' => ($data['group_type_id'] - 2),
                'date_initiated' => Carbon::now(),
                // 'affiliation_id' => $data['affiliation_id'] ?? null, DEPRECATED CGSP-1023
                'current_step' => 1,
            ]);
            $expertPanel->uuid = $uuid;
            $group->expertPanel()->save($expertPanel);
        }

        event(new GroupCreated($group));

        return $group;
    }

    public function asController(ActionRequest $request)
    {
        $data = $request->only('name', 'group_type_id', 'group_status_id', 'group_visibility_id', 'parent_id', 'long_base_name', 'short_base_name', 'website_url', 'affiliation_id');

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
            'group_visibility_id' => 'nullable|exists:group_visibilities,id',
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value != 0) {
                        if (!DB::table('groups')->where('id', $value)->exists()) {
                            $fail('The selected parent is not valid.');
                        }
                    }
                }
            ],
            'affiliation_id' => ['nullable', 'digits:5', Rule::unique('groups', 'affiliation_id'),
                function ($attribute, $value, $fail) {
                    if ($value === null) { return; }

                    $groupTypeId = (int) request()->input('group_type_id');
                    $expectedPrefix = match ($groupTypeId) {
                        (int) config('groups.types.gcep.id') => '4',
                        (int) config('groups.types.vcep.id'), (int) config('groups.types.scvcep.id') => '5',
                        (int) config('groups.types.wg.id'), (int) config('groups.types.cdwg.id'), (int) config('groups.types.sccdwg.id') => '6',
                        default => null,
                    };
                    if ($expectedPrefix && !str_starts_with($value, $expectedPrefix)) {
                        $fail('The Affiliation ID does not match the selected group type.');
                    }
                }
            ],
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
