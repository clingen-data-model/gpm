<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use App\Models\AnnualReview;
use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsController;

class AnnualReviewSubmit
{
    use AsController;

    public function handle(AnnualReview $annualReview): AnnualReview
    {
        $annualReview->update(['completed_at' => Carbon::now()]);
        return $annualReview;
    }

    public function asController(ActionRequest $request, $groupUuid, $annualReviewId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $annualReview = $group->expertPanel->annualReviews()->find($annualReviewId);

        $validator = $this->makeDataValidator($annualReview);
        if ($validator->fails()) {
            return response([
                'message' => 'There are unmet requirements.',
                'requirement_violations' => $validator->errors()
            ], 400);
        }

        return $this->handle($annualReview);
    }

    public function authorize(ActionRequest $request)
    {
        $group = Group::findByUuidOrFail($request->group);
        return Auth::user()->can('manageAnnualReview', $group);
    }

    private function makeDataValidator(AnnualReview $annualReview)
    {
        $data = array_merge(
            ['submitter_id' => $annualReview->submitter_id],
            $annualReview->data ?? []
        );

        return Validator::make(
            $data,
            $this->getRequirements($annualReview),
            [
                    'required' => 'This is required.',
                    'required_if' => 'This is required.',
                    'in' => 'The selection is invalid.',
                    'numeric' => 'This must be a number.',
                    'accepted' => 'This is required.'
            ]
        );
    }

    private function getRequirements(AnnualReview $annualReview): array
    {
        $requirements = [
            'submitter_id' => [
                'required',
                'exists:group_members,id',
            ],
            'grant' => 'required|in:Broad/Geisinger,Stanford/Baylor,UNC,Unsure',
            'membership_attestation' => 'required',
            'ongoing_plans_updated' => 'required|in:yes,no',
            'ongoing_plans_update_details' => 'required_if:ongoing_plans_updated,yes',
            'goals' => 'required',
            'cochair_commitment' => 'required|in:yes,no',
            'cochair_commitment_details' => 'required_if:cochair_commitment,yes',
            'applied_for_funding' => 'required|in:yes,no',
            'funding' => 'required_if:applied_for_funding,yes',
            'funding_other_details' => 'required_if:funding,other',
            'funding_thoughts' => 'required_if:applied_for_funding,no',
            'website_attestation' => 'accepted'
        ];

        if ($annualReview->expertPanel->isGcep) {
            $requirements = array_merge(
                $requirements,
                [
                    'ep_activity' => 'required|in:active,inactive',
                    'submitted_inactive_form' => 'nullable|required_if:ep_activity,inactive|in:yes,no',
                    'gci_use' => 'required|in:yes,no',
                    'gci_use_details' => 'required_if:gci_use,no',
                    'published_count' => 'required|numeric',
                    'approved_unpublished_count' => 'required|numeric',
                    'in_progress_count' => 'required|numeric',
                    'gt_gene_list' => 'required|in:yes,no',
                    'gt_gene_list_details' => 'required_if:gt_gene_list,no',
                    'gt_precuration_info' => 'required|in:yes,no',
                    'gt_precuration_info_details' => 'required_if:gt_precuration_info,no',
                    'recuration_begun' => 'required|in:yes,no',
                    'recuration_designees' => 'required',

                ]
            );
        }
        if ($annualReview->expertPanel->isVcep) {
            $requirements = array_merge($requirements, [
                'vci_use' => 'required|in:yes,no',
                'vci_use_details' => 'required_if:gci_use,no',
                'vcep_totals' => 'required|json', //need to do more in depth validation somewhere.
                'variant_workflow_changes' => 'required|in:yes,no',
                'variant_workflow_changes_details' => 'required_if:variant_workflow_changes,yes',
                'rereview_discrepencies_progress' => 'required',
                'rereview_lp_and_vus_progress' => 'required',
                'rereview_lb_progress' => 'required',
                'member_designation_changed' => 'required|in:yes,no',
                'specification_plans' => 'required|in:yes,no',
                'specification_plans_details' => 'required_if:specification_plans,yes',
            ]);
        }
        return $requirements;
    }
}
