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
use App\Modules\ExpertPanel\Service\AffilsClient;
use App\Modules\ExpertPanel\Actions\AffiliationCreate; 
use Illuminate\Support\Facades\Log;

class GroupCreate
{
    use AsController;

    public function __construct(
        private CoiCodeMake $makeCoiCode, 
        private AffilsClient $affils,
        private AffiliationCreate $affiliationCreate) {
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

        // If CDWG, send data AM API with ONLY { name }
        if ((int) $group->group_type_id === 2) {
            try {
                $resp = $this->affils->createCdwg(['name' => $group->name]);
                if (isset($resp['id'])) {
                    $group->parent_id = (int) $resp['id'];
                    $group->save();
                }
            } catch (\RuntimeException $e) {
                Log::warning('CDWG create failed on Affils API', [
                    'group_id' => $group->id,
                    'message'  => $e->getMessage(),
                    'status'   => $e->getCode(),
                ]);
                
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => [$e->getMessage()],
                ]);
            }
        }

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

            try {
                $this->affiliationCreate->handle($expertPanel);
            } catch (\RuntimeException $e) {
                Log::warning('EP affiliation create failed', [
                    'group_uuid'        => $group->uuid,
                    'expert_panel_uuid' => $expertPanel->uuid,
                    'message'           => $e->getMessage(),
                    'status'            => $e->getCode(),
                ]);
            }
        }

         // If CDWG, send data AM API with ONLY { name }
        if ((int) $group->group_type_id === 2) {
            try {
                $resp = $this->affils->createCdwg(['name' => $group->name]);
                if (isset($resp['id'])) {
                    $group->parent_id = (int) $resp['id'];
                    $group->save();
                }
            } catch (\RuntimeException $e) {
                Log::warning('CDWG create failed on Affils API', [
                    'group_id' => $group->id,
                    'message'  => $e->getMessage(),
                    'status'   => $e->getCode(),
                ]);
                
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => [$e->getMessage()],
                ]);
            }
        }

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

            try {
                $this->affiliationCreate->handle($expertPanel);
            } catch (\RuntimeException $e) {
                Log::warning('EP affiliation create failed', [
                    'group_uuid'        => $group->uuid,
                    'expert_panel_uuid' => $expertPanel->uuid,
                    'message'           => $e->getMessage(),
                    'status'            => $e->getCode(),
                ]);
            }
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
            'name'            => 'required|max:255',
            'long_base_name'  => 'max:255',
            'short_base_name' => 'max:16',
            'group_type_id'   => 'required',
            'group_status_id' => 'required|exists:group_statuses,id',
            'parent_id'       => [
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
            'name.required'            => 'Please enter a group name.',
            'name.max'                 => 'The group name can’t be longer than :max characters.',
            
            'long_base_name.max'       => 'Long base name can’t be longer than :max characters.',
            'short_base_name.max'      => 'Short base name can’t be longer than :max characters.',
            
            'group_type_id.required'   => 'Please choose a group type (CDWG, GCEP, VCEP, or SCVCEP).',
            
            'group_status_id.required' => 'Please choose a group status.',
            'group_status_id.exists'   => 'Please select a valid status from the list.',
            
            // If you switch parent_id to use a normal exists rule, this will be used:
            // 'parent_id.required'       => 'Please choose a parent group.',
            'parent_id.exists'         => 'The selected parent group could not be found.',
            
            'required'                 => 'This field is required.',
            'exists'                   => 'The selected value is invalid.',
            'max'                      => 'Please keep this value under :max characters.',
        ];
    }

    private function resolveParentId($data): ?int
    {
        return isset($data['parent_id']) && $data['parent_id'] > 0 ? $data['parent_id'] : null;
    }
}
