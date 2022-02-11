<?php

namespace App\Modules\Group\Actions;

use App\Models\AnnualUpdate;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class AnnualUpdateCreate
{
    use AsController;

    public function handle(ExpertPanel $expertPanel): AnnualUpdate
    {
        $annualReview = AnnualUpdate::create(['expert_panel_id' => $expertPanel->id]);

        return $annualReview;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        if (!$group->isEp) {
            throw ValidationException::withMessages(['The group must be an expert panel']);
        }

        return $this->handle($group->expertPanel);
    }

    public function authorize(ActionRequest $request)
    {
        return Auth::user()->can('manageAnnualUpdate', $request->group);
    }
}
