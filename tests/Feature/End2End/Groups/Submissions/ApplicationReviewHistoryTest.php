<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use App\Models\ApplicationSnapshot;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Tests\TestCase;

class ApplicationReviewHistoryTest extends TestCase
{
    private $group;
    private string $url;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->actingAs($this->setupUserWithPerson(null, ['ep-applications-manage']));
        $this->group = ExpertPanel::factory()->create()->group;
        $this->url = '/api/groups/'.$this->group->uuid.'/application/review-history';
    }

    private function submission(array $attributes = []): Submission
    {
        return Submission::factory()->create(array_merge(['group_id' => $this->group->id,
            'submission_type_id' => 1, 'submission_status_id' => 1, 'data' => null], $attributes));
    }

    private function snapshot(Submission $submission, string $name = 'Frozen'): ApplicationSnapshot
    {
        return ApplicationSnapshot::create(['group_id' => $submission->group_id, 'submission_id' => $submission->id,
            'version' => 99, 'snapshot' => ['attributes' => ['name' => $name], 'relations' => []]]);
    }

    public function test_initial_round_has_snapshot_only_submitted_state(): void
    {
        $submission = $this->submission(['notes' => 'Submitter note']);
        $this->snapshot($submission);
        $this->getJson($this->url)->assertOk()->assertJsonPath('cycles.0.title', 'Initial Application')
            ->assertJsonPath('cycles.0.rounds.0.review_round', 1)->assertJsonPath('cycles.0.rounds.0.is_current_review', true)
            ->assertJsonPath('cycles.0.rounds.0.submitter_notes', 'Submitter note')
            ->assertJsonPath('cycles.0.rounds.0.detail.mode', 'submitted_state');
        $before = $this->getJson($this->url.'/'.$submission->id)->assertOk()
            ->assertJsonPath('submitted_state.0.value', 'Frozen')->assertJsonPath('submitted_state.6.available', false)->json();
        $this->group->update(['name' => 'Live edited name']);
        $this->assertSame($before, $this->getJson($this->url.'/'.$submission->id)->json());
    }

    public function test_rounds_are_chronological_with_id_ties_and_notes_and_approval(): void
    {
        $later = $this->submission(['created_at' => '2026-02-01', 'submission_status_id' => 4]);
        $first = $this->submission(['created_at' => '2026-01-01', 'submission_status_id' => 2,
            'response_content' => 'Please revise', 'closed_at' => '2026-01-02']);
        $middle = $this->submission(['created_at' => '2026-01-01', 'submission_status_id' => 2]);
        ScopeOfWorkVersion::create(['group_id' => $this->group->id, 'major_version' => 1, 'minor_version' => 0, 'status' => 'approved']);
        $response = $this->getJson($this->url)->assertOk()->assertJsonPath('cycles.0.approval.relationship', 'unrecorded')
            ->assertJsonPath('cycles.0.approval.submission_id', $later->id)->assertJsonPath('initial_scope_of_work_version.label', '1.0')
            ->assertJsonPath('cycles.0.rounds.0.revisions_requested_notes', 'Please revise');
        $this->assertSame([$first->id, $middle->id, $later->id], array_column($response->json('cycles.0.rounds'), 'submission_id'));
        $this->assertSame([1, 2, 3], array_column($response->json('cycles.0.rounds'), 'review_round'));
    }

    public function test_missing_ambiguous_deleted_and_invalid_pointer_snapshots(): void
    {
        $missing = $this->submission();
        $ambiguous = $this->submission();
        $this->snapshot($ambiguous);
        $this->snapshot($ambiguous);
        $deleted = $this->submission();
        $snapshot = $this->snapshot($deleted);
        \DB::table('application_snapshots')->where('id', $snapshot->id)->update(['deleted_at' => now()]);
        $invalid = $this->submission(['data' => ['application_snapshot_id' => $snapshot->id]]);
        $this->snapshot($invalid);
        $response = $this->getJson($this->url)->assertOk()->assertJsonCount(4, 'cycles.0.rounds');
        $this->assertSame(['unavailable', 'ambiguous', 'unavailable', 'unavailable'],
            array_column(array_column($response->json('cycles.0.rounds'), 'snapshot'), 'availability'));
        $this->getJson($this->url.'/'.$missing->id)->assertOk()->assertJsonPath('submitted_state', null);
    }

    public function test_initial_round_two_compares_immediate_frozen_snapshot(): void
    {
        $first = $this->submission(['submission_status_id' => 2]);
        $second = $this->submission();
        $this->snapshot($first, 'Before');
        $this->snapshot($second, 'After');
        $this->getJson($this->url.'/'.$second->id)->assertOk()->assertJsonPath('mode', 'previous_review_round')
            ->assertJsonPath('previous_submission_id', $first->id)->assertJsonPath('comparison.changes.0.before', 'Before')
            ->assertJsonPath('comparison.changes.0.after', 'After')->assertJsonPath('comparison.status', 'partial');
    }

    public function test_missing_immediate_predecessor_does_not_skip_back(): void
    {
        $first = $this->submission();
        $middle = $this->submission();
        $last = $this->submission();
        $this->snapshot($first);
        $this->snapshot($last);
        $this->getJson($this->url.'/'.$last->id)->assertOk()->assertJsonPath('previous_submission_id', $middle->id)
            ->assertJsonPath('availability', 'unavailable')->assertJsonPath('comparison.changes', []);
    }

    public function test_step_four_excluded_and_contradictory_context_reported(): void
    {
        $this->submission();
        $step4 = $this->submission(['submission_type_id' => 2]);
        $metadata4 = $this->submission(['data' => ['approval_step' => 4]]);
        $this->submission(['data' => ['step' => 4]]);
        $this->submission(['data' => ['context' => 'scope_of_work_revision']]);
        $this->getJson($this->url)->assertOk()->assertJsonCount(1, 'cycles')->assertJsonCount(1, 'cycles.0.rounds')
            ->assertJsonCount(1, 'ambiguous_submissions');
        $this->getJson($this->url.'/'.$step4->id)->assertNotFound();
        $this->getJson($this->url.'/'.$metadata4->id)->assertNotFound();
    }

    public function test_scope_of_work_cycles_reuse_frozen_comparison_and_explicit_approval(): void
    {
        $base = ScopeOfWorkVersion::create(['group_id' => $this->group->id, 'major_version' => 1, 'status' => 'approved']);
        $base->snapshots()->create(['snapshot_schema_version' => '1.0.0', 'snapshot' => ['group' => ['name' => 'Baseline']]]);
        $version = ScopeOfWorkVersion::create(['group_id' => $this->group->id, 'major_version' => 2, 'status' => 'approved', 'base_version_id' => $base->id]);
        $attributes = ['scope_of_work_version_id' => $version->id, 'data' => ['context' => 'scope_of_work_revision', 'base_version_id' => $base->id, 'approval_step' => 1]];
        $first = $this->submission(array_merge($attributes, ['submission_status_id' => 2]));
        $second = $this->submission(array_merge($attributes, ['submission_status_id' => 4]));
        $this->snapshot($first, 'First');
        $this->snapshot($second, 'Second');
        $version->update(['submission_id' => $second->id]);
        $this->getJson($this->url)->assertOk()->assertJsonPath('cycles.0.key', 'scope_of_work:'.$version->id)
            ->assertJsonPath('cycles.0.approval.relationship', 'explicit')->assertJsonPath('cycles.0.approval.submission_id', $second->id)
            ->assertJsonPath('cycles.0.rounds.1.review_round', 2);
        $this->getJson($this->url.'/'.$first->id)->assertOk()->assertJsonPath('comparison.mode', 'approved_baseline')
            ->assertJsonPath('comparison.changes.0.before', 'Baseline');
        $this->getJson($this->url.'/'.$second->id)->assertOk()->assertJsonPath('comparison.mode', 'previous_review_round')
            ->assertJsonPath('comparison.changes.0.before', 'First')->assertJsonPath('comparison.changes.0.after', 'Second');
        $version->update(['submission_id' => $first->id]);
        $this->getJson($this->url)->assertOk()->assertJsonPath('cycles.0.approval.relationship', 'unavailable');
    }

    public function test_cross_group_detail_and_snapshot_pointer_are_rejected(): void
    {
        $other = ExpertPanel::factory()->create()->group;
        $foreign = $this->submission(['group_id' => $other->id]);
        $snapshot = $this->snapshot($foreign);
        $local = $this->submission(['data' => ['application_snapshot_id' => $snapshot->id]]);
        $this->getJson($this->url.'/'.$foreign->id)->assertNotFound();
        $this->getJson($this->url.'/'.$local->id)->assertOk()->assertJsonPath('availability', 'unavailable');
    }

    public function test_non_reviewer_is_forbidden_on_both_endpoints(): void
    {
        $submission = $this->submission();
        $this->actingAs($this->setupUserWithPerson());
        $this->getJson($this->url)->assertForbidden();
        $this->getJson($this->url.'/'.$submission->id)->assertForbidden();
    }

    public function test_legacy_approval_without_submission_does_not_create_a_round(): void
    {
        $this->group->expertPanel->update(['step_1_approval_date' => '2020-01-01']);
        $this->getJson($this->url)->assertOk()->assertJsonPath('cycles', [])
            ->assertJsonPath('initial_step_1_approval.relationship', 'unrecorded');
    }

    public function test_explicit_initial_link_is_only_reported_when_recorded(): void
    {
        $submission = $this->submission(['submission_status_id' => 4]);
        ScopeOfWorkVersion::create(['group_id' => $this->group->id, 'major_version' => 1, 'minor_version' => 0,
            'status' => 'approved', 'submission_id' => $submission->id]);
        $this->getJson($this->url)->assertOk()->assertJsonPath('initial_version_relationship', 'explicit')
            ->assertJsonPath('cycles.0.approval.relationship', 'explicit')->assertJsonPath('cycles.0.scope_of_work_version.label', '1.0');
    }

    public function test_cycles_are_grouped_by_identity_not_label_and_current_marker_requires_active_pointer(): void
    {
        foreach ([1, 2] as $index) {
            $version = ScopeOfWorkVersion::create(['group_id' => $this->group->id, 'major_version' => 2, 'status' => 'submitted']);
            $submission = $this->submission(['scope_of_work_version_id' => $version->id,
                'data' => ['context' => 'scope_of_work_revision', 'approval_step' => 1]]);
            if ($index === 2) $version->update(['submission_id' => $submission->id]);
        }
        $this->getJson($this->url)->assertOk()->assertJsonCount(2, 'cycles')
            ->assertJsonPath('cycles.0.rounds.0.review_round', 1)->assertJsonPath('cycles.1.rounds.0.review_round', 1)
            ->assertJsonPath('cycles.0.rounds.0.is_current_review', true)->assertJsonPath('cycles.1.rounds.0.is_current_review', false);
    }

    public function test_step_four_sow_round_is_never_shown_as_history_or_as_comparison_baseline(): void
    {
        $version = ScopeOfWorkVersion::create(['group_id' => $this->group->id, 'major_version' => 2, 'status' => 'submitted']);
        $step4 = $this->submission(['scope_of_work_version_id' => $version->id,
            'data' => ['context' => 'scope_of_work_revision', 'approval_step' => 4]]);
        $step1 = $this->submission(['scope_of_work_version_id' => $version->id,
            'data' => ['context' => 'scope_of_work_revision', 'approval_step' => 1]]);
        $this->snapshot($step4, 'Step four private detail');
        $this->snapshot($step1);
        $this->getJson($this->url)->assertOk()->assertJsonCount(1, 'cycles.0.rounds');
        $this->getJson($this->url.'/'.$step4->id)->assertNotFound();
        $this->getJson($this->url.'/'.$step1->id)->assertOk()->assertJsonPath('availability', 'unavailable')
            ->assertJsonPath('comparison', null);
    }

    public function test_foreign_version_association_is_ambiguous_and_detail_is_rejected(): void
    {
        $other = ExpertPanel::factory()->create()->group;
        $version = ScopeOfWorkVersion::create(['group_id' => $other->id, 'major_version' => 2, 'status' => 'submitted']);
        $submission = $this->submission(['scope_of_work_version_id' => $version->id,
            'data' => ['context' => 'scope_of_work_revision']]);
        $this->getJson($this->url)->assertOk()->assertJsonPath('cycles', [])->assertJsonCount(1, 'ambiguous_submissions');
        $this->getJson($this->url.'/'.$submission->id)->assertNotFound();
    }

    public function test_deleted_round_is_retained_and_explicit_snapshot_pointer_resolves_duplicates(): void
    {
        $first = $this->submission();
        $snapshot = $this->snapshot($first, 'Selected');
        $this->snapshot($first, 'Other snapshot');
        $first->update(['data' => ['application_snapshot_id' => $snapshot->id]]);
        $first->delete();
        $second = $this->submission();
        $this->snapshot($second, 'Next');
        $this->getJson($this->url)->assertOk()->assertJsonCount(2, 'cycles.0.rounds')
            ->assertJsonPath('cycles.0.rounds.0.snapshot.availability', 'available');
        $this->getJson($this->url.'/'.$second->id)->assertOk()->assertJsonPath('comparison.changes.0.before', 'Selected');
        $this->getJson($this->url.'/'.$first->id)->assertOk()->assertJsonPath('submitted_state.0.value', 'Selected');
    }

    public function test_comment_and_approval_reviewers_have_read_access(): void
    {
        $submission = $this->submission();
        foreach (['ep-applications-comment', 'ep-applications-approve'] as $permission) {
            $this->actingAs($this->setupUserWithPerson(null, [$permission]));
            $this->getJson($this->url)->assertOk();
            $this->getJson($this->url.'/'.$submission->id)->assertOk();
        }
    }

    public function test_chair_judgements_are_separate_from_explicit_final_approver(): void
    {
        $initial = $this->submission(['submission_status_id' => 4]);
        $initial->judgements()->create(['person_id' => $initial->submitter_id, 'decision' => 'approve', 'notes' => 'Chair recommendation']);
        $version = ScopeOfWorkVersion::create(['group_id' => $this->group->id, 'major_version' => 2,
            'status' => 'approved', 'approved_by' => auth()->id()]);
        $sow = $this->submission(['submission_status_id' => 4, 'scope_of_work_version_id' => $version->id,
            'data' => ['context' => 'scope_of_work_revision', 'approval_step' => 1]]);
        $version->update(['submission_id' => $sow->id]);
        $this->getJson($this->url)->assertOk()->assertJsonPath('cycles.0.rounds.0.reviewed_by.user_id', auth()->id())
            ->assertJsonPath('cycles.1.rounds.0.reviewed_by', null)
            ->assertJsonPath('cycles.1.rounds.0.reviewer_judgements.0.notes', 'Chair recommendation');
    }
}
