<?php

namespace App\Modules\Group\Actions;

use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\ExpertPanelAffiliationIdUpdated;

class ExpertPanelAffiliationIdUpdate
{
    use AsController;

    public function handle(Group $group, $affiliationId): Group
    {
        if ($affiliationId != $group->expertPanel->affiliation_id) {
            $group->expertPanel->update(['affiliation_id' => $affiliationId]);

            event(new ExpertPanelAffiliationIdUpdated($group, $affiliationId));
        }

        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        return $this->handle($group, $request->affiliation_id);
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user()->can('groups-manage');
    }

    public function rules(ActionRequest $request): array
    {
        $expertPanel = $request->group->expertPanel;
        return [
           'affiliation_id' => [
                'nullable',
                'size:5',
                function ($attribute, $value, $fail) use ($expertPanel) {
                    if ($expertPanel->group->isVcep && substr($value, 0, 1) != '5') {
                        $fail('VCEP affiliation IDs must start with "5"');
                    }
                    if ($expertPanel->group->isGcep && substr($value, 0, 1) != '4') {
                        $fail('GCEP affiliation IDs must start with "4"');
                    }
                },
                Rule::unique('expert_panels', 'affiliation_id')
                    ->ignore($expertPanel->id)
                    ->where(function ($query) use ($expertPanel) {
                        $query->whereNotNull('affiliation_id')
                            ->whereNull('deleted_at');
                    })
           ],
        ];
    }
}
