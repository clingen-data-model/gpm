<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ClinvarOrganizationUpdated;
use App\Modules\Group\Http\Resources\GroupResource;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ClinvarOrganizationUpdate
{
    use AsController;

    public function handle(ExpertPanel $expertPanel, string $clinvarOrgId): ExpertPanel
    {
        $old = $expertPanel->clinvar_org_id;

        if ($old === $clinvarOrgId) {
            return $expertPanel;
        }

        $expertPanel->clinvar_org_id = $clinvarOrgId;
        $expertPanel->save();

        event(new ClinvarOrganizationUpdated(
            $expertPanel,
            $expertPanel->clinvar_org_id,
            $old
        ));

        return $expertPanel->refresh();
    }

    public function asController(ActionRequest $request, ExpertPanel $expertPanel)
    {
        $data  = $request->validated();
        $group = $expertPanel->group;

        if (! $group) {
            abort(404, 'Group not found for this Expert Panel.');
        }

        if ($group->group_type_id !== config('groups.types.vcep.id')) {
            abort(422, 'ClinVar Organization ID can only be set for VCEP groups.');
        }

        $this->handle($expertPanel, $data['clinvar_org_id']);
        $group->load('expertPanel');
        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'clinvar_org_id' => ['required', 'string', 'max:20'],
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['super-user', 'super-admin'])) {
            return true;
        }
        if (! $user->person) {
            return false;
        }

        $expertPanel = $request->route('expertPanel');
        if (! $expertPanel instanceof ExpertPanel) {
            return false;
        }

        $group = $expertPanel->group;
        if (! $group) {
            return false;
        }

        return $group->memberIsCoordinator($user->person->id);
    }

    public function getValidationMessages(): array
    {
        return [
            'clinvar_org_id.required' => 'ClinVar Organization ID field is required.',
        ];
    }
}
