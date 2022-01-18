<?php

namespace Database\Factories;

use App\Models\AnnualReview;
use App\Modules\Group\Models\GroupMember;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnualReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnnualReview::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $expertPanel = ExpertPanel::factory()->create();
        // $groupMember = GroupMember::factory()->create(['group_id', $expertPanel->group_id]);
        return [
            'expert_panel_id' => $expertPanel->id,
            'data' => $this->createData()
        ];
    }

    public function createData($data = []): array
    {
        $reviewData = [
            'grant' => $this->faker->randomElement(['Broad/Geisinger','Stanford/Baylor','UNC','Unsure']),
            'ep_activity' => 'active',
            'submitted_inactive_form' => null,
            'membership_attestation' => 'yes',
            'applied_for_funding' => 'yes',
            'funding' => 'Pharma',
            'funding_thoughts' => null,
            'website_attestation' => true,
            'ongoing_plans_updated' => 'yes',
            'ongoing_plans_update_details' => 'test ongoing_plans_update_details',
            'goals' => 'We have many goals',
            'cochair_commitment' => 'no',
            'cochair_commitment_details' => 'test cochair_commitment_details',
        ];

        return array_merge($reviewData, $this->gcepData(), $this->vcepData(), $data);
    }

    private function gcepData($data = [])
    {
        $returnValue = [
            'gci_use' => 'no',
            'gci_use_details' => 'test gci_use_details',
            'gt_gene_list' => 'no',
            'gt_gene_list_details' => 'test gt_gene_list_details',
            'gt_precuration_info' => 'no',
            'gt_precuration_info_details' => 'test gt_precuration_info_details',
            'published_count' => 10,
            'approved_unpublished_count' => 11,
            'in_progress_count' => 13,
            'recuration_begun' => 'yes',
            'recuration_designees' => 'Bob Dobbs, bob@dobs.com',
        ];

        return array_merge($returnValue, $data);
    }
    
    private function vcepData($data = [])
    {
        $returnValue = [
            'vci_use' => 'no',
            'vci_use_details' => 'test vci_use_details',
            'sepcification_progress' => 'yes',
            'specification_url' => 'https://www.google.com',
            'variant_counts' => [
                [
                    'gene' => 'ABC1',
                    'in_clinvar' => 10,
                    'gci_approved' => 2,
                    'provisionally_approved' => 4
                ]
            ],
            'variant_workflow_changes' => 'yes',
            'variant_workflow_changes_details' => 'test variant_workflow_changes_details',
            'specification_progress' => 'yes',
            'specification_progress_details' => 'test specification_progress_details',
            'specification_plans' => null,
            'specification_plans_details' => 'test specification_plans_details',
            'rereview_discrepencies_progress' => 'discrepencies blah',
            'rereview_lp_and_vus_progress' => 'lp and vus blah',
            'rereview_lb_progress' => 'lb blah',
            'member_designation_changed' => 'yes',
        ];

        return array_merge($returnValue, $data);
    }
    

    private function yesOrNo()
    {
        return $this->faker->randomElement(['yes', 'no']);
    }
}
