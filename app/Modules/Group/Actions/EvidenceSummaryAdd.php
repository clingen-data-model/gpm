<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\Group\Events\EvidenceSummaryAdded;

class EvidenceSummaryAdd
{
    use AsObject;
    use AsController;

    public function handle(Group $group, array $data): EvidenceSummary
    {
        if (!$group->isVcepOrScvcep) {
            throw ValidationException::withMessages(['group' => ['You can not add an evidence summary to this group. Only VCEPs have evidence summaries.']]);
        }

        $evidenceSummary = $group->expertPanel->evidenceSummaries()->save(new EvidenceSummary($data));
        event(new EvidenceSummaryAdded($group, $evidenceSummary));

        return $evidenceSummary;
    }

    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        
        if (Auth::user()->cannot('addEvidenceSummary', $group)) {
            throw new AuthorizationException('You do not have permission to add an example evidence summary for this VCEP.');
        }

        $evidenceSummary = $this->handle($group, $request->all());
        $evidenceSummary->load('gene');
        $evidenceSummary->gene?->append('gt_gene');
        return ['data' => $evidenceSummary];
    }

    public function rules()
    {
        return [
            'gene_id' => 'required|exists:genes,id',
            'variant' => 'required|max:255',
            'summary' => 'required|max:66535',
            'vci_url' => 'nullable|url'
        ];
    }

    public function getValidationMessages()
    {
        return [
            'required' => 'This field is required.',
            'gene_id.exists' => 'The gene was not found in your scope.',
        ];
    }
}
