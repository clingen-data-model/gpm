<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Models\ApplicationSnapshot;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Services\ApplicationReviewHistory;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Database\Seeders\NextActionTypesTableSeeder;
use Database\Seeders\NextActionAssigneesTableSeeder;
use PHPUnit\Framework\Attributes\DataProvider;

class InitialApplicationReviewRoundsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed([SubmissionTypeAndStatusSeeder::class, NextActionTypesTableSeeder::class, NextActionAssigneesTableSeeder::class, \Database\Seeders\CurationReviewProtocolsSeeder::class]);
        $this->actingAs($this->setupUserWithPerson(null, ['ep-applications-manage']));
    }

    public static function types(): array { return [['gcep'], ['vcep'], ['scvcep']]; }
    public static function variantTypes(): array { return [['vcep'], ['scvcep']]; }

    private function panel(string $type): ExpertPanel
    {
        $panel = ExpertPanel::factory()->create(['current_step' => 1]);
        $panel->update(['expert_panel_type_id' => config("expert_panels.types.$type.id")]);
        $panel->group->update(['group_type_id' => config("groups.types.$type.id")]);
        return $panel->fresh();
    }

    private function submit(ExpertPanel $panel): Submission
    {
        return app(ApplicationSubmitStep::class)->handle($panel->group->fresh(), auth()->user()->person, 'Rationale');
    }

    private function detail(Submission $submission): array
    {
        return $this->getJson('/api/groups/'.$submission->group->uuid.'/application/review-history/'.$submission->id)->assertOk()->json();
    }

    #[DataProvider('types')]
    public function test_initial_rounds_use_frozen_state_and_immediate_same_step_predecessor(string $type): void
    {
        $panel = $this->panel($type);
        $panel->update(['scope_description' => 'Round one scope']);
        // A pre-existing version must never become the initial application's baseline.
        ScopeOfWorkVersion::create(['group_id' => $panel->group_id, 'major_version' => 1, 'status' => 'approved']);
        $first = $this->submit($panel);
        $detail = $this->detail($first);
        $this->assertSame('submitted_state', $detail['mode']);
        $this->assertNull($detail['previous_submission_id']);
        $sections = collect($detail['submitted_state'])->keyBy('label');
        $this->assertSame('Round one scope', $sections['Description of scope']['value']);
        $this->assertSame($type !== 'gcep', $sections->has('Membership description'));
        $this->assertSame($type === 'gcep', $sections->has('GCI Training Date'));
        $this->assertSame($type === 'scvcep', $sections->has('Re-review Likely Oncogenic and VUS classifications'));
        $this->assertSame($type === 'vcep', $sections->has('Re-review LP and VUS classifications'));
        $this->assertEmpty($detail['comparison']['changes']);
        $first->reject('Round one note');
        $panel->update(['scope_description' => 'Round two scope']);
        $second = $this->submit($panel);
        $second->reject('Round two note');
        $panel->update(['scope_description' => 'Round three scope']);
        $third = $this->submit($panel);
        foreach ([[$second, $first, 'Round one scope', 'Round two scope'], [$third, $second, 'Round two scope', 'Round three scope']] as [$current, $previous, $before, $after]) {
            $result = $this->detail($current);
            $this->assertSame('previous_review_round', $result['mode']);
            $this->assertSame($previous->id, $result['previous_submission_id']);
            $change = collect($result['comparison']['changes'])->firstWhere('label', 'Description of scope');
            $this->assertSame($before, $change['before']);
            $this->assertSame($after, $change['after']);
        }
        $panel->update(['scope_description' => 'Unsubmitted later edit']);
        $this->assertSame($detail, $this->detail($first));
    }

    #[DataProvider('variantTypes')]
    public function test_step_four_captures_its_own_evidence_and_compares_only_step_four(string $type): void
    {
        $panel = $this->panel($type);
        $step1 = $this->submit($panel);
        $step1->update(['submission_status_id' => 4, 'closed_at' => now()]);
        $panel->forceFill(['current_step' => 4, 'curation_review_process_notes' => 'Initial review process'])->save();
        $this->setupRoles(['core-approval-member', 'biocurator-trainer', 'biocurator'], 'group');
        $member = \App\Modules\Group\Models\GroupMember::factory()->create(['group_id' => $panel->group_id,
            'training_level_1' => true, 'training_level_2' => true]);
        $member->roles()->sync(config('permission.models.role')::whereIn('name', ['core-approval-member', 'biocurator-trainer', 'biocurator'])->where('scope', 'group')->pluck('id'));
        $panel->update(['curation_review_protocol_id' => 1, 'meeting_frequency' => 'Weekly']);
        $gene = Gene::factory()->create(['expert_panel_id' => $panel->id, 'gene_symbol' => 'CAPTURED']);
        $evidence = EvidenceSummary::create(['expert_panel_id' => $panel->id, 'gene_id' => $gene->id,
            'variant' => 'Variant A', 'summary' => 'Frozen evidence', 'assertion_id' => 'CIVIC:123', 'vci_url' => 'https://example.test/captured']);
        $first = $this->submit($panel);
        $detail = $this->detail($first);
        $sections = collect($detail['submitted_state'])->keyBy('label');
        $this->assertSame(4, $detail['step']);
        $this->assertSame('submitted_state', $detail['mode']);
        $this->assertFalse($sections->has('Genes'));
        $this->assertFalse($sections->has('Description of scope'));
        $this->assertSame($evidence->id, $sections['Evidence Summaries']['value'][0]['id']);
        $this->assertSame($evidence->id, data_get(ApplicationSnapshot::findOrFail($first->data['application_snapshot_id'])->snapshot, 'review_state.evidence_summaries.0.id'));
        $this->assertSame('Frozen evidence', $sections['Evidence Summaries']['value'][0]['summary']);
        $this->assertSame($type === 'scvcep', array_key_exists('assertion_id', $sections['Evidence Summaries']['value'][0]));
        $this->assertTrue($sections->has(strtoupper($type === 'scvcep' ? 'sc-vcep' : $type).' Standardized Review Process'));
        $this->assertTrue($sections['Core Approval Members']['available']);
        $this->assertSame($member->person->name, $sections['Core Approval Members']['value'][0]['name']);
        $this->assertFalse($sections['Core Approval Members']['value'][0]['attestation_completed']);
        $this->assertCount(1, $sections['Trained Biocurators']['value']);
        $this->assertCount(1, $sections['Biocurator Trainers']['value']);
        $this->assertSame($type === 'vcep', $sections->has('Meeting/call frequency'));
        $this->assertSame('Process #1: Biocurator review followed by '.($type === 'scvcep' ? 'SC-VCEP' : 'VCEP').' discussion',
            $sections[($type === 'scvcep' ? 'SC-VCEP' : 'VCEP').' Standardized Review Process']['value']);
        $this->assertNotSame($step1->data['application_snapshot_id'], $first->data['application_snapshot_id']);
        $first->reject('Please revise Step 4');
        $evidence->update(['summary' => 'Revised evidence']);
        $panel->update(['curation_review_process_notes' => 'Revised process', 'curation_review_protocol_id' => 2]);
        $second = $this->submit($panel);
        $next = $this->detail($second);
        $this->assertSame($first->id, $next['previous_submission_id']);
        $change = collect($next['comparison']['changes'])->firstWhere('label', 'Evidence Summaries');
        $this->assertSame($evidence->id, $change['before'][0]['id']);
        $this->assertSame($evidence->id, $change['after'][0]['id']);
        $this->assertSame('Frozen evidence', $change['before'][0]['summary']);
        $this->assertSame('Revised evidence', $change['after'][0]['summary']);
        $member->person->update(['first_name' => 'Later edited']);
        $member->roles()->detach();
        $evidence->delete();
        $this->assertSame($detail, $this->detail($first));
        $history = app(ApplicationReviewHistory::class)->handle($panel->group);
        $this->assertSame([1, 4], collect($history['cycles'])->pluck('step')->sort()->values()->all());
        $this->assertSame(['initial_application'], collect($history['cycles'])->pluck('lifecycle_key')->unique()->values()->all());
    }

    public function test_old_step_four_data_is_unavailable_instead_of_using_step_one(): void
    {
        $panel = $this->panel('vcep');
        $first = $this->submit($panel);
        $fourth = Submission::factory()->create(['group_id' => $panel->group_id, 'submission_type_id' => 2,
            'data' => ['application_snapshot_id' => $first->data['application_snapshot_id']]]);
        $this->assertSame('unavailable', $this->detail($fourth)['availability']);
        $fourth->update(['data' => null]);
        $snapshot = ApplicationSnapshot::create(['group_id' => $panel->group_id, 'submission_id' => $fourth->id, 'version' => 100,
            'snapshot' => ['attributes' => ['group_type_id' => config('groups.types.vcep.id')],
                'relations' => ['expertPanel' => ['attributes' => ['current_step' => 1]]]]]);
        $this->assertSame('unavailable', $this->detail($fourth)['availability']);
        $snapshot->update(['snapshot' => ['attributes' => ['group_type_id' => config('groups.types.vcep.id')],
            'relations' => ['expertPanel' => ['attributes' => ['current_step' => 4]]]]]);
        $sections = collect($this->detail($fourth)['submitted_state'])->keyBy('label');
        $this->assertFalse($sections['Evidence Summaries']['available']);
    }

    public function test_sow_history_uses_explicit_step_four_metadata(): void
    {
        $panel = $this->panel('vcep');
        $version = ScopeOfWorkVersion::create(['group_id' => $panel->group_id, 'major_version' => 2, 'status' => 'submitted']);
        Submission::factory()->create(['group_id' => $panel->group_id, 'submission_type_id' => 2,
            'scope_of_work_version_id' => $version->id, 'data' => ['context' => 'scope_of_work_revision', 'approval_step' => 4]]);
        $cycle = app(ApplicationReviewHistory::class)->handle($panel->group)['cycles'][0];
        $this->assertSame(4, $cycle['step']);
        $this->assertSame('Sustained Curation', $cycle['step_name']);
        $this->assertSame('Scope of Work Version 2.0', $cycle['title']);
        $this->assertSame('scope_of_work:'.$version->id, $cycle['lifecycle_key']);
    }

    public function test_gcep_has_no_step_four_history(): void
    {
        $panel = $this->panel('gcep');
        $this->submit($panel);
        Submission::factory()->create(['group_id' => $panel->group_id, 'submission_type_id' => 2]);
        $this->assertSame([1], collect(app(ApplicationReviewHistory::class)->handle($panel->group)['cycles'])->pluck('step')->all());
    }
}
