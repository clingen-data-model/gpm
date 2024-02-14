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
            // SubmitterInformation
            // submitter_id is in top level
            'grant',
            'ep_activity',
            'submitted_inactive_form',
            // MembershipUpdate
            'membership_attestation',
            'expert_panels_change',
            // GciGtUse (GCEP)
            'gci_use',
            'gci_use_details',
            'gt_gene_list',
            'gt_gene_list_details',
            'gt_precuration_info',
            'gt_precuration_info_details',
            // GeneCurationTotals (GCEP)
            'published_count',
            'approved_unpublished_count',
            'in_progress_count',
            'publishing_issues',
            // GcepOngoingPlansUpdateForm (GCEP)
            // 'ongoing_plans_updated', // same var used in VCEP form, leaving here as comment to show it's also in a GCEP form
            // 'ongoing_plans_update_details', // same var used in VCEP form
            // GcepOngoingPlansForm (GCEP) appears to interact directly through `group`?
            // GcepRereviewForm (GCEP)
            'recuration_begun',
            'recuration_designees',
            // VciUse (VCEP)
            'vci_use',
            'vci_use_details',
            // GoalsForm
            'goals',
            'cochair_commitment',
            'cochair_commitment_details',
            'long_term_chairs',
            // FundingForm
            'applied_for_funding',
            'funding',
            'funding_other_details',
            'funding_thoughts',
            // WebsiteAttestation
            'website_attestation',
            // SpecificationProgress (VCEP with approved draft)
            'specifications_for_new_gene',
            'specifications_for_new_gene_details',
            'submit_clinvar',
            'vcep_publishing_issues',
            'system_discrepancies',
            // VariantReanalysis (VCEP with sustained curation)
            'rereview_discrepencies_progress',
            // VcepOnGoingPlansUpdateForm (VCEP with sustained curation)
            'ongoing_plans_updated',
            'ongoing_plans_update_details',
            'changes_to_call_frequency',
            'changes_to_call_frequency_details',
            // MemberDesignationUpdate (VCEP with sustained curation)
            'member_designation_changed',
        ];
    }
}
