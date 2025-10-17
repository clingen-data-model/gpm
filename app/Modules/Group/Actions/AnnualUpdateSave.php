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
            'expert_panels_change', // obsolete 2024
            // GciGtUse (GCEP)
            'gci_use',
            'gci_use_details',
            'gt_gene_list',
            'gt_gene_list_details',
            'gt_precuration_info',
            'gt_precuration_info_details',
            // GeneCurationTotals (GCEP)
            'published_count', // obsolete 2024
            'approved_unpublished_count', // obsolete 2024
            'in_progress_count',
            'publishing_issues',
            // GcepOngoingPlansUpdateForm (GCEP)
            // 'ongoing_plans_updated', // same var used in VCEP form, leaving here as comment to show it's also in a GCEP form
            // 'ongoing_plans_update_details', // same var used in VCEP form
            // GcepOngoingPlansForm (GCEP) appears to interact directly through `group`?
            // GcepRereviewForm (GCEP)
            'recuration_begun',
            'recuration_designees',
            // VciUse (VCEP) // obsolete 2024
            'vci_use', // obsolete 2024
            'vci_use_details', // obsolete 2024
            // GoalsForm
            'goals',
            'cochair_commitment',
            'cochair_commitment_details',
            'long_term_chairs', // obsolete 2024
            // FundingForm
            'applied_for_funding', // obsolete 2024
            'funding', // obsolete 2024
            'funding_other_details', // obsolete 2024
            'funding_thoughts', // obsolete 2024
            // ExternalFundingForm
            'external_funding', // new 2024
            'external_funding_type', // new 2024
            'external_funding_other_details', // new 2024
            'funding_plans', // new 2024
            'funding_plans_details', // new 2024
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
            'difficulty_adhering_to_vcep_policies',
            // MemberDesignationUpdate (VCEP with sustained curation)
            'member_designation_changed',

            // --- NEW: Publications snapshot GPM-470 ---
            'publications',
            'publications_note',
        ];
    }
}
