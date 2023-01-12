<?php

namespace App\Modules\Group\Actions;

use App\Models\AnnualUpdate;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AnnualUpdateSave
{
    use AsController;

    public function handle(AnnualUpdate $annualReview, $submitterId, $data): AnnualUpdate
    {
        $annualReview->submitter_id = $submitterId;
        $annualReview->data = $data;
        $annualReview->save();

        return $annualReview;
    }

    public function asController(ActionRequest $request, $groupUuid, $annualReviewId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $annualReview = $group->expertPanel
                            ->annualUpdates()
                            ->find($annualReviewId);
        $submitterId = $request->get('submitter_id');
        $data = collect($request->get('data'))
                    ->only($this->getDataFields())
                    ->toArray();
        return $this->handle($annualReview, $submitterId, $data);
    }

    public function authorize(ActionRequest $request)
    {
        $group = Group::findByUuidOrFail($request->group);
        return Auth::user()->can('manageAnnualUpdate', $group);
    }

    private function getDataFields(): array
    {
        return [
            'submitter_id',
            'grant',
            'ep_activity',
            'submitted_inactive_form',
            'membership_attestation',
            'applied_for_funding',
            'funding',
            'funding_thoughts',
            'website_attestation',
            'ongoing_plans_updated',
            'ongoing_plans_update_details',
            'changes_to_call_frequency',
            'changes_to_call_frequency_details',
            'gci_use',
            'gci_use_details',
            'gt_gene_list',
            'gt_gene_list_details',
            'gt_precuration_info',
            'gt_precuration_info_details',
            'published_count',
            'approved_unpublished_count',
            'in_progress_count',
            'recuration_begun',
            'recuration_designees',
            'vci_use',
            'vci_use_details',
            'goals',
            'cochair_commitment',
            'cochair_commitment_details',
            'specification_progress',
            'specification_progress_details',
            'specifications_for_new_gene',
            'specifications_for_new_gene_details',
            'specification_url',
            'variant_counts',
            // 'variant_workflow_changes',
            // 'variant_workflow_changes_details',
            'specification_progress',
            'specification_progress_url',
            // 'specification_plans',
            // 'specification_plans_details',
            'rereview_discrepencies_progress',
            'rereview_lp_and_vus_progress',
            'rereview_lb_progress',
            'member_designation_changed',
        ];
    }
}
