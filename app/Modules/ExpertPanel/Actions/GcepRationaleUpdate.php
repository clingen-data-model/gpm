<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\ExpertPanel\Events\GcepRationaleUpdated;

class GcepRationaleUpdate
{
    use AsAction;

    public function handle(ExpertPanel $expertPanel, ?string $gcepRationale): ExpertPanel
    {
        if ($expertPanel->prioritization_rationale == $gcepRationale) {
            return $expertPanel;
        }

        $expertPanel->update([
            'prioritization_rationale' => $gcepRationale
        ]);

        event(new GcepRationaleUpdated($expertPanel, $gcepRationale));
        return $expertPanel->refresh();
    }
    
    public function asController(ActionRequest $request, ExpertPanel $expertPanel)
    {
        $data  = $request->validated();
        $group = $expertPanel->group;

        if (! $group) {
            abort(404, 'Group not found for this Expert Panel.');
        }

        if (! $group->is_gcep) {
            abort(422, 'GCEP Rationale can only be set for GCEP groups.');
        }

        return $this->handle($expertPanel, $data['prioritization_rationale']);
    }

    public function rules(): array
    {
        return [
            'prioritization_rationale' => 'required|max:66535'
        ];
    }

    public function getValidationMessages(): array
    {
        return ['required' => 'This field is required.'];
    }
}
