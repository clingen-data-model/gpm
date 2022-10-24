<?php

namespace App\Modules\ExpertPanel\Actions;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Events\CoiCompleted;

class CoiResponseStore
{
    use AsAction;

    public function handle(String $coiCode, ?int $groupMemberId, array $responseData)
    {
        $group = Group::findByCoiCodeOrFail($coiCode);
        $groupMember = GroupMember::findOrFail($groupMemberId);

        $coi = Coi::create([
            'uuid'=>Uuid::uuid4()->toString(),
            'group_member_id' => $groupMemberId,
            'data' => $this->makeFullResponseData($responseData, $groupMember),
            'group_id' => $group->id,
            'completed_at' => Carbon::now()
        ]);

        Event::dispatch(new CoiCompleted($group, $coi));

        return $coi;
    }

    public function asController($coiCode, ActionRequest $request)
    {
        $this->handle($coiCode, $request->group_member_id, $request->except('group_member_id'));
        return response(['message' => 'COI response stored.'], 200);
    }

    public function rules(ActionRequest $request): array
    {
        return [
            'work_fee_lab' => 'required',
            'contributions_to_gd_in_group' => 'required',
            'contributions_to_genes' => 'required_if:contributions_to_gd_in_group,1',
            'coi' => 'required',
            'coi_details' => [Rule::requiredIf(fn () => in_array($request->coi, [1,2]))],
            'coi_attestation' => 'required|accepted',
            'data_policy_attestation' => 'required|accepted'
        ];
    }

    public function getValidationMessages()
    {
        return [
            'required_if' => 'This is required.'
        ];
    }

    private function makeFullResponseData(array $responseData, GroupMember $groupMember): array
    {
        return array_merge(
            $responseData,
            [
                'first_name' => $groupMember->person->first_name,
                'last_name' => $groupMember->person->last_name,
                'email' => $groupMember->person->email,
            ]
        );
    }

}
