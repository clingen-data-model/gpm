<?php

namespace App\Modules\Group\Actions;

use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\GroupAffiliationIdUpdated;
use App\Modules\Group\Actions\WorkingGroupAffiliationIdGenerate;

class GroupAffiliationIdUpdate
{
    use AsController;

    public function __construct(
        private WorkingGroupAffiliationIdGenerate $wgAffiliationIdGenerator
    ) {
    }

    public function handle(Group $group, ?string $affiliationId): Group
    {
        if ($affiliationId !== $group->affiliation_id) {

            if($group->isWorkingGroupType) {
                $user = Auth::user();
                if(! $user || ! $user->hasRole('super-user')) {
                    throw ValidationException::withMessages(['affiliation_id' => 'Only super-admins can edit Affiliation IDs for Working Groups, CDWGs, and SC-CDWGs.']);
                }
                // JUST IN CASE to anticipate somehow a user who is other than super-user and they are trying to set the affiliation_id to null, we will generate a new one for them.
                if ($affiliationId === null) {
                    $affiliationId = $this->wgAffiliationIdGenerator->handle($group);
                }
            } 
            
            $group->update(['affiliation_id' => $affiliationId]);
            event(new GroupAffiliationIdUpdated($group, $affiliationId));
        }
        return $group;
    }

    public function asController(ActionRequest $request, Group $group): Group
    {
        return $this->handle($group, $request->validated('affiliation_id'));
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user()->can('groups-manage');
    }

    public function rules(ActionRequest $request): array
    {
        /** @var Group $group */
        $group = $request->route('group');
        return [
            'affiliation_id' => [
                'nullable',
                'digits:5',
                function ($attribute, $value, $fail) use ($group) {
                    if ($value === null) { return; }
                    if (($group->isVcepOrScvcep) && !str_starts_with($value, '5')) {
                        $fail('VCEP and SC-VCEP Affiliation IDs must start with "5".');
                    }
                    if ($group->isGcep && !str_starts_with($value, '4')) {
                        $fail('GCEP Affiliation IDs must start with "4".');
                    }
                     if (($group->isWorkingGroupType) && !str_starts_with($value, '6')) {
                        $fail('Working Group (including CDWG and SCCDWG) Affiliation IDs must start with "6".');
                    }
                },
                Rule::unique('groups', 'affiliation_id')->ignore($group->id),
            ],
        ];
    }
}