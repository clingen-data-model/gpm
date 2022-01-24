<?php

namespace Tests\Feature\End2End\Groups;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\AnnualReview;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubmitAnnualReviewTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = $this->setupUser(permissions: ['annual-reviews-manage']);
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->coordinator = GroupMember::factory()
                                ->create(['group_id' => $this->expertPanel->group->id])
                                ->assignRole('coordinator');

        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2022-02-16');
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_save_annual_review()
    {
        $this->user->revokePermissionTo('annual-reviews-manage');

        $annualReview = AnnualReview::factory()->create(['expert_panel_id' => $this->expertPanel->id]);

        $this->makeRequest($annualReview)
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function stores_completed_at_date_when_submitted_by_privilegged_user()
    {
        $annualReview = AnnualReview::factory()
                            ->create([
                                'expert_panel_id' => $this->expertPanel->id,
                                'submitter_id' => $this->coordinator->id,
                            ]);

        $this->makeRequest($annualReview)
            ->assertStatus(200);

        $this->assertDatabaseHas('annual_reviews', [
            'id' => $annualReview->id,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * @test
     */
    public function validates_base_required_fields_for_gcep()
    {
        $annualReview = AnnualReview::create(['expert_panel_id'=>$this->expertPanel->id]);
        
        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'ongoing_plans_updated',
            'goals',
            'cochair_commitment',
            'applied_for_funding',
            'website_attestation',
            'ep_activity',
            'gci_use',
            'published_count',
            'approved_unpublished_count',
            'in_progress_count',
            'gt_gene_list',
            'gt_precuration_info',
            'recuration_begun',
            'recuration_designees',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    /**
     * @test
     */
    public function does_not_require_fields_after_ep_activity_if_inactive_for_gceps()
    {
        $annualReview = AnnualReview::create([
            'expert_panel_id'=>$this->expertPanel->id,
            'submitter_id' => $this->coordinator->id,
            'data' => [
                'grant' => 'UNC',
                'ep_activity' => 'inactive',
                'submitted_inactive_form' => 'yes'
            ]
        ]);
        
        $response = $this->makeRequest($annualReview)
            ->assertStatus(200);

        $this->assertDatabaseHas('annual_reviews', [
            'id' => $annualReview->id,
            'completed_at' => Carbon::now()
        ]);
    }
    

    /**
     * @test
     */
    public function validates_conditionally_required_fields_for_gceps()
    {
        $annualReview = AnnualReview::create([
            'expert_panel_id'=>$this->expertPanel->id,
            'data' => [
                'ongoing_plans_updated' => 'yes',
                'cochair_commitment' => 'no',
                'applied_for_funding' => 'yes',
                'funding' => 'other',
                'ep_activity' => 'active',
                'gci_use' => 'no',
                'gt_gene_list' => 'no',
                'gt_precuration_info' => 'no',
            ]
        ]);

        $conditionalRequiredFields = [
            'gci_use_details',
            'gt_gene_list_details',
            'gt_precuration_info_details',
            'ongoing_plans_update_details',
            'cochair_commitment_details',
            'funding_other_details',
        ];

        $response = $this->makeRequest($annualReview)
                        ->assertStatus(422);

        foreach ($conditionalRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    /**
     * @test
     */
    public function validates_base_required_fields_for_vcep()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
            'step_3_approval_date' => Carbon::now(),
            'step_4_approval_date' => Carbon::now(),
        ]);
        $annualReview = AnnualReview::create(['expert_panel_id'=>$this->expertPanel->id]);
        
        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'ongoing_plans_updated',
            'goals',
            'cochair_commitment',
            'applied_for_funding',
            'website_attestation',
            'vci_use',
            'variant_counts',
            'variant_workflow_changes',
            'rereview_discrepencies_progress',
            'rereview_lp_and_vus_progress',
            'rereview_lb_progress',
            'member_designation_changed',
            'specification_plans',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    /**
     * @test
     */
    public function only_validates_def_fields_if_not_draft_approved()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
        ]);
        $annualReview = AnnualReview::create(['expert_panel_id'=>$this->expertPanel->id]);
        
        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'ongoing_plans_updated',
            'goals',
            'cochair_commitment',
            'applied_for_funding',
            'website_attestation',
            'vci_use',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    /**
     * @test
     */
    public function validates_base_def_and_spec_fields_if_spec_approved()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
        ]);
        $annualReview = AnnualReview::create(['expert_panel_id'=>$this->expertPanel->id]);
        
        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'ongoing_plans_updated',
            'goals',
            'cochair_commitment',
            'applied_for_funding',
            'website_attestation',
            'vci_use',
            'variant_counts',
            'specification_progress',
            'specification_progress_url',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }
    
    /**
     * @test
     */
    public function validates_base_sustained_curation_fields_if_approved()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
            'step_3_approval_date' => Carbon::now(),
            'step_4_approval_date' => Carbon::now(),
        ]);
        $annualReview = AnnualReview::create(['expert_panel_id'=>$this->expertPanel->id]);
        
        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'ongoing_plans_updated',
            'goals',
            'cochair_commitment',
            'applied_for_funding',
            'website_attestation',
            'vci_use',
            'variant_counts',
            'specification_progress',
            'specification_progress_url',
            'variant_workflow_changes',
            'rereview_discrepencies_progress',
            'rereview_lp_and_vus_progress',
            'rereview_lb_progress',
            'member_designation_changed',
            'specification_plans',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }
    
    
    /**
     * @test
     */
    public function validates_conditionally_required_fields_for_vceps()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
            'step_3_approval_date' => Carbon::now(),
            'step_4_approval_date' => Carbon::now(),
        ]);
        $annualReview = AnnualReview::create([
            'expert_panel_id'=>$this->expertPanel->id,
            'data' => [
                'ongoing_plans_updated' => 'yes',
                'cochair_commitment' => 'no',
                'applied_for_funding' => 'yes',
                'funding' => 'other',
                'vci_use' => 'no',
                'variant_workflow_changes' => 'yes',
                'specification_plans' => 'yes',
            ]
        ]);

        $conditionalRequiredFields = [
            'vci_use_details',
            'variant_workflow_changes_details',
            'ongoing_plans_update_details',
            'cochair_commitment_details',
            'funding_other_details',
            'specification_plans_details'
        ];

        $response = $this->makeRequest($annualReview)
                        ->assertStatus(422);

        foreach ($conditionalRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }


    private function makeRequest($annualReview)
    {
        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/annual-reviews/'.$annualReview->id;
        return $this->json('POST', $url);
    }
}
