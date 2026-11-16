<?php

namespace Tests\Feature\End2End\Groups;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\AnnualUpdate;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Database\Seeders\AnnualUpdateWindowSeeder;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class SubmitAnnualUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGrouptest();
        $this->runSeeder(AnnualUpdateWindowSeeder::class);

        $this->user = $this->setupUser(permissions: ['annual-updates-manage']);
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->coordinator = GroupMember::factory()
                                ->create(['group_id' => $this->expertPanel->group->id])
                                ->assignRole('coordinator');

        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2022-02-16');
    }

    #[Test]
    public function unprivileged_user_cannot_save_annual_update()
    {
        $this->user->revokePermissionTo('annual-updates-manage');

        $annualReview = AnnualUpdate::factory()->create(['expert_panel_id' => $this->expertPanel->id]);

        $this->makeRequest($annualReview)
            ->assertStatus(403);
    }

    #[Test]
    public function stores_completed_at_date_when_submitted_by_privilegged_user()
    {
        $annualReview = AnnualUpdate::factory()
                            ->create([
                                'expert_panel_id' => $this->expertPanel->id,
                                'submitter_id' => $this->coordinator->id,
                                'data' => $this->makeGcepSubmitData(),
                            ]);

        $this->makeRequest($annualReview)
            ->assertStatus(200);

        $this->assertDatabaseHas('annual_updates', [
            'id' => $annualReview->id,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    #[Test]
    public function validates_base_required_fields_for_gcep()
    {
        $annualReview = AnnualUpdate::create(['expert_panel_id'=>$this->expertPanel->id]);

        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'goals',
            'cochair_commitment',
            'external_funding',
            'funding_plans',
            'gci_use',
            'in_progress_count',
            'gt_gene_list',
            'gt_precuration_info',
            'recuration_begun',
            'recuration_designees',
            'ongoing_plans_updated',
        ];

        foreach ($baseRequiredFields as $field) {
            $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    #[Test]
    public function does_not_require_fields_after_ep_activity_if_inactive_for_gceps()
    {
        $annualReview = AnnualUpdate::create([
            'expert_panel_id'=>$this->expertPanel->id,
            'submitter_id' => $this->coordinator->id,
            'data' => [
                'grant' => 'UNC',
                'ep_activity' => 'inactive',
                'submitted_inactive_form' => 'yes',
                'publications_reviewed' => true
            ]
        ]);

        $response = $this->makeRequest($annualReview)
            ->assertStatus(200);

        $this->assertDatabaseHas('annual_updates', [
            'id' => $annualReview->id,
            'completed_at' => Carbon::now()
        ]);
    }


    #[Test]
    public function validates_conditionally_required_fields_for_gceps()
    {
        $annualReview = AnnualUpdate::create([
            'expert_panel_id'=>$this->expertPanel->id,
            'submitter_id' => $this->coordinator->id,
            'data' => [
                'ongoing_plans_updated' => 'yes',
                'external_funding' => 'yes',
                'external_funding_type' => 'other',
                'funding_plans' => 'yes',
                'ep_activity' => 'active',
                'gci_use' => 'no',
                'gt_gene_list' => 'no',
                'gt_precuration_info' => 'no',
            ]
        ]);

        $conditionalRequiredFields = [
            'gci_use_details',
            'external_funding_other_details',
            'funding_plans_details',
            'gt_gene_list_details',
            'gt_precuration_info_details',
            'ongoing_plans_update_details',
        ];

        $response = $this->makeRequest($annualReview)
                        ->assertStatus(422);

        foreach ($conditionalRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    #[Test]
    public function validates_base_required_fields_for_vcep()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => config('expert_panels.types.vcep.id'),
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
            'step_3_approval_date' => Carbon::now(),
            'step_4_approval_date' => Carbon::now(),
        ]);
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.vcep.id')]);
        $annualReview = AnnualUpdate::create(['expert_panel_id'=>$this->expertPanel->id]);

        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'goals',
            'cochair_commitment',
            'external_funding',
            'funding_plans',
            'submit_clinvar',
            'changes_to_call_frequency'
        ];

        foreach ($baseRequiredFields as $field) {
            $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    #[Test]
    public function only_validates_def_fields_if_not_draft_approved()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
        ]);
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.vcep.id')]);
        $annualReview = AnnualUpdate::create(['expert_panel_id'=>$this->expertPanel->id]);

        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'goals',
            'cochair_commitment',
            'external_funding',
            'funding_plans',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    #[Test]
    public function validates_base_def_and_spec_fields_if_spec_approved()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
        ]);
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.vcep.id')]);
        $annualReview = AnnualUpdate::create(['expert_panel_id'=>$this->expertPanel->id]);

        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'goals',
            'cochair_commitment',
            'external_funding',
            'funding_plans',
            'submit_clinvar',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }

    #[Test]
    public function validates_base_sustained_curation_fields_if_approved()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
            'step_3_approval_date' => Carbon::now(),
            'step_4_approval_date' => Carbon::now(),
        ]);
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.vcep.id')]);
        $annualReview = AnnualUpdate::create(['expert_panel_id'=>$this->expertPanel->id]);

        $response = $this->makeRequest($annualReview)
            ->assertStatus(422);

        $baseRequiredFields = [
            'submitter_id',
            'grant',
            'membership_attestation',
            'goals',
            'cochair_commitment',
            'external_funding',
            'funding_plans',
            'submit_clinvar',
            'system_discrepancies',
            'changes_to_call_frequency',
            'specifications_for_new_gene',
        ];

        foreach ($baseRequiredFields as $field) {
            $response = $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }


    #[Test]
    public function validates_conditionally_required_fields_for_vceps()
    {
        $this->expertPanel->update([
            'expert_panel_type_id' => 2,
            'step_1_approval_date' => Carbon::now(),
            'step_2_approval_date' => Carbon::now(),
            'step_3_approval_date' => Carbon::now(),
            'step_4_approval_date' => Carbon::now(),
        ]);
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.vcep.id')]);
        $annualReview = AnnualUpdate::create([
            'expert_panel_id'=>$this->expertPanel->id,
            'submitter_id' => $this->coordinator->id,
            'data' => [
                'ongoing_plans_updated' => 'yes',
                'external_funding' => 'yes',
                'external_funding_type' => 'other',
                'funding_plans' => 'yes',
                'submit_clinvar' => 'yes',
                'system_discrepancies' => 'none',
                'changes_to_call_frequency' => 'yes',
                'cochair_commitment' => 'yes',
                'vci_use' => 'no',
                'specifications_for_new_gene' => 'yes',
            ]
        ]);

        $conditionalRequiredFields = [
            'vci_use_details',
            'external_funding_other_details',
            'funding_plans_details',
            'changes_to_call_frequency_details',
            'specifications_for_new_gene_details',
        ];

        $response = $this->makeRequest($annualReview)
                        ->assertStatus(422);

        foreach ($conditionalRequiredFields as $field) {
            $response->assertJsonFragment([$field => ['This is required.']]);
        }
    }


    #[Test]
    public function snapshots_all_visible_group_publications_and_allows_reuse_in_later_updates()
    {
        $model = \App\Modules\Group\Models\Publication::class;
        $pub = $model::create(['group_id' => $this->expertPanel->group_id, 'source' => 'pmid', 'identifier' => '123',
            'meta' => ['title' => 'Original title', 'authors' => ['Author']], 'published_at' => '1999-01-01']);
        $preprint = $model::create(['group_id' => $pub->group_id, 'source' => 'doi', 'identifier' => '10.1/preprint', 'meta' => ['title' => 'Preprint']]);
        $preprint->published_publication_id = $pub->id;
        $preprint->save();
        $unlinked = $model::create(['group_id' => $pub->group_id, 'source' => 'pmid', 'identifier' => 'unlinked', 'pub_type' => 'preprint']);
        $foreign = $model::create(['group_id' => \App\Modules\Group\Models\Group::factory()->create()->id, 'source' => 'pmid', 'identifier' => '123']);
        $deleted = $model::create(['group_id' => $pub->group_id, 'source' => 'pmid', 'identifier' => 'deleted']);
        $deleted->delete();
        $data = $this->makeGcepSubmitData(['publications' => [['publication_id' => $foreign->uuid]], 'publications_note' => 'Keep note']);
        $review = AnnualUpdate::factory()->create(['expert_panel_id' => $this->expertPanel->id, 'submitter_id' => $this->coordinator->id, 'data' => $data]);
        $later = AnnualUpdate::factory()->create(['expert_panel_id' => $this->expertPanel->id, 'submitter_id' => $this->coordinator->id, 'data' => $data]);
        $response = $this->makeRequest($review)->assertOk()->assertJsonCount(2, 'data.publications');
        $snapshots = collect($response->json('data.publications'))->keyBy('publication_id');
        $this->assertSame('Preprint', $snapshots[$pub->uuid]['preprint']['title']);
        $this->assertTrue($snapshots->has($unlinked->uuid));
        $this->assertArrayNotHasKey('included', $snapshots[$pub->uuid]);
        $snapshot = $review->fresh()->data;
        $this->assertSame('Keep note', $snapshot['publications_note']);
        $this->assertTrue($snapshot['publications_reviewed']);
        $this->assertSame($data['goals'], $snapshot['goals']);
        $pub->update(['meta' => ['title' => 'Changed']]);
        $this->makeRequest($review)->assertOk();
        $this->assertSame($snapshot, $review->fresh()->data);
        $this->makeRequest($later)->assertOk()->assertJsonCount(2, 'data.publications');
        $url = '/api/groups/'.$this->expertPanel->group->uuid;
        $this->putJson($url.'/expert-panel/annual-updates/'.$review->id, ['data' => $data])->assertStatus(409);
        $this->assertSame($snapshot, $review->fresh()->data);
        $this->getJson($url.'/publications?shape_exchange=true')->assertOk()->assertJsonCount(2);
    }

    #[Test]
    public function requires_publication_review_confirmation_even_with_no_publications()
    {
        foreach ([null, false, 'invalid'] as $value) {
            $data = $this->makeGcepSubmitData(['publications_reviewed' => $value]);
            if ($value === null) unset($data['publications_reviewed']);
            $review = AnnualUpdate::factory()->create(['expert_panel_id' => $this->expertPanel->id, 'submitter_id' => $this->coordinator->id, 'data' => $data]);
            $this->makeRequest($review)->assertUnprocessable()->assertJsonValidationErrors('publications_reviewed');
            $this->assertNull($review->fresh()->completed_at);
        }
        $review->update(['data' => $this->makeGcepSubmitData()]);
        $this->makeRequest($review)->assertOk()->assertJsonPath('data.publications', [])->assertJsonPath('data.publications_reviewed', true);
    }

    private function makeRequest($annualReview)
    {
        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/annual-updates/'.$annualReview->id;
        return $this->json('POST', $url);
    }

    private function makeGcepSubmitData(array $overrides = []): array
    {
        return array_merge([
            'publications_reviewed' => true,
            'grant' => 'UNC',
            'ep_activity' => 'active',
            'membership_attestation' => 'yes',
            'goals' => 'Goal summary',
            'cochair_commitment' => 'yes',
            'external_funding' => 'no',
            'funding_plans' => 'no',
            'gci_use' => 'yes',
            'gt_gene_list' => 'yes',
            'gt_precuration_info' => 'yes',
            'in_progress_count' => 0,
            'recuration_begun' => 'yes',
            'recuration_designees' => 'Test coordinator',
            'ongoing_plans_updated' => 'no',
        ], $overrides);
    }
}
