<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Models\ApplicationSnapshot;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild;
use App\Modules\Group\Actions\ScopeOfWork\RevisionRefresh;
use Database\Seeders\SubmissionTypeAndStatusSeeder;

class ScopeOfWorkGeneDiscardTest extends TestCase
{
    private ExpertPanel $panel;
    private ScopeOfWorkVersion $baseline;
    private Gene $gene;
    private Gene $duplicate;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->actingAs($this->setupUserWithPerson(null, ['groups-manage', 'ep-applications-manage']));
        $this->panel = ExpertPanel::factory()->vcep()->create(['scope_description' => 'Approved', 'date_completed' => now()]);
        $this->gene = $this->panel->genes()->create(['gene_symbol' => 'SAME', 'hgnc_id' => 10,
            'mondo_id' => 'MONDO:1', 'disease_name' => 'Before', 'tier' => 1]);
        $this->duplicate = $this->panel->genes()->create(['gene_symbol' => 'SAME', 'hgnc_id' => 10,
            'mondo_id' => 'MONDO:2', 'tier' => 1]);
        $this->baseline = ScopeOfWorkVersion::create(['group_id' => $this->panel->group_id,
            'expert_panel_id' => $this->panel->id, 'major_version' => 1, 'status' => 'approved']);
        $this->baseline->snapshots()->create(['snapshot_schema_version' => '1.0.0',
            'snapshot' => SnapshotBuild::run($this->panel->group->fresh())]);
    }

    private function refresh(): ScopeOfWorkVersion
    {
        return RevisionRefresh::run($this->panel->group->fresh());
    }

    private function url(ScopeOfWorkVersion $revision, string $rule, ?string $field = null): string
    {
        $change = $revision->changes()->where('rule_key', $rule)->when($field,
            fn ($query) => $query->where('field_name', $field))->sole();
        return '/api/groups/'.$this->panel->group->uuid.'/scope-of-work/revisions/'.$revision->uuid.'/changes/'.$change->id.'/discard';
    }

    public function test_added_gene_is_soft_deleted_without_deleting_captured_history(): void
    {
        $added = $this->panel->genes()->create(['gene_symbol' => 'NEW', 'hgnc_id' => 20]);
        $capture = $added->snapshots()->create(['check_key' => 'test', 'payload' => ['historical' => true], 'captured_at' => now()]);
        $this->gene->update(['tier' => 2]);
        $revision = $this->refresh();
        $submission = Submission::factory()->create(['group_id' => $this->panel->group_id,
            'scope_of_work_version_id' => $revision->id, 'submission_status_id' => config('submissions.statuses.revisions-requested.id')]);
        $snapshot = ApplicationSnapshot::create(['group_id' => $this->panel->group_id,
            'submission_id' => $submission->id, 'version' => 1, 'snapshot' => ['captured_gene_id' => $added->id]]);
        $baseData = $this->baseline->latestSnapshot->snapshot;
        $this->postJson($this->url($revision, 'gene.add'))->assertOk()
            ->assertJsonPath('active_revision.summary.total_changes', 1);
        $this->assertSoftDeleted($added);
        $this->assertSame(2, $this->gene->fresh()->tier);
        $this->assertNotNull($capture->fresh());
        $this->assertSame(['captured_gene_id' => $added->id], $snapshot->fresh()->snapshot);
        $this->assertSame($baseData, $this->baseline->latestSnapshot->fresh()->snapshot);
    }

    public function test_removed_gene_restores_exact_id_and_captured_attributes_only(): void
    {
        $this->gene->update(['disease_name' => 'Changed before removal']);
        $this->gene->delete();
        $this->duplicate->update(['tier' => 2]);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'gene.remove'))->assertOk();
        $restored = Gene::findOrFail($this->gene->id);
        $this->assertSame('Before', $restored->disease_name);
        $this->assertSame(1, $restored->tier);
        $this->assertSame($this->panel->id, $restored->expert_panel_id);
        $this->assertSame(2, $this->duplicate->fresh()->tier);
        $this->assertSame(2, $this->panel->genes()->count());
    }

    public function test_tier_discard_keeps_other_fields_on_same_gene_including_unrefreshed_edits(): void
    {
        $this->gene->update(['tier' => 2, 'disease_name' => 'Edited']);
        $revision = $this->refresh();
        $this->gene->update(['disease_name' => 'Latest saved edit']);
        $this->postJson($this->url($revision, 'gene.update_tier'))->assertOk()
            ->assertJsonPath('active_revision.summary.total_changes', 1);
        $this->assertSame(1, $this->gene->fresh()->tier);
        $this->assertSame('Latest saved edit', $this->gene->fresh()->disease_name);
        $this->assertSame(1, $this->duplicate->fresh()->tier);
    }

    public function test_field_discard_restores_only_selected_field_and_preserves_explicit_null(): void
    {
        $this->gene->update(['tier' => 2, 'disease_name' => 'Edited', 'moi' => 'AD']);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'gene.update', 'disease_name'))->assertOk();
        $this->assertSame('Before', $this->gene->fresh()->disease_name);
        $this->assertSame('AD', $this->gene->fresh()->moi);
        $this->assertSame(2, $this->gene->fresh()->tier);
        $this->postJson($this->url($revision, 'gene.update', 'moi'))->assertOk();
        $this->assertNull($this->gene->fresh()->moi);
    }

    public function test_stale_tokens_and_fresh_value_mismatch_are_conflicts(): void
    {
        $this->gene->update(['tier' => 2]);
        $revision = $this->refresh();
        $stale = $this->url($revision, 'gene.update_tier');
        $this->refresh();
        $this->postJson($stale)->assertStatus(409);
        $current = $this->url($revision, 'gene.update_tier');
        $this->gene->update(['tier' => 3]);
        $this->postJson($current)->assertStatus(409);
        $this->assertSame(3, $this->gene->fresh()->tier);
    }

    public function test_legacy_field_change_without_gene_id_is_rejected_not_matched_by_label(): void
    {
        $this->gene->update(['tier' => 2]);
        $revision = $this->refresh();
        $change = $revision->changes()->where('rule_key', 'gene.update_tier')->sole();
        $change->update(['before_value' => ['tier' => 1], 'after_value' => ['tier' => 2]]);
        $this->postJson($this->url($revision, 'gene.update_tier'))->assertStatus(409);
        $this->assertSame(2, $this->gene->fresh()->tier);
        $this->assertSame(1, $this->duplicate->fresh()->tier);
    }

    public function test_submitted_revision_and_unauthorized_user_cannot_discard(): void
    {
        $this->gene->update(['tier' => 2]);
        $revision = $this->refresh();
        $url = $this->url($revision, 'gene.update_tier');
        $revision->update(['status' => 'submitted']);
        $this->postJson($url)->assertStatus(409);
        $revision->update(['status' => 'draft']);
        $this->actingAs($this->setupUserWithPerson());
        $this->postJson($url)->assertForbidden();
        $this->assertSame(2, $this->gene->fresh()->tier);
    }

    public function test_discard_added_gene_requires_remove_permission_not_just_add_permission(): void
    {
        $this->panel->genes()->create(['gene_symbol' => 'NEW', 'hgnc_id' => 20]);
        $revision = $this->refresh();
        $this->actingAs($this->setupUserWithPerson(null, ['ep-applications-manage']));
        $this->postJson($this->url($revision, 'gene.add'))->assertForbidden();
        $this->actingAs($this->setupUserWithPerson(null, ['groups-manage']));
        $this->postJson($this->url($revision, 'gene.add'))->assertOk();
    }

    public function test_final_gene_change_closes_revision(): void
    {
        $this->gene->update(['tier' => 2]);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'gene.update_tier'))->assertOk()
            ->assertJsonPath('has_active_revision', false);
        $this->assertSame('discarded', $revision->fresh()->status);
    }

    public function test_stored_json_key_order_does_not_make_an_added_gene_token_stale(): void
    {
        $added = $this->panel->genes()->create(['gene_symbol' => 'NEW', 'hgnc_id' => 20,
            'plan' => ['is_other' => true, 'notes' => 'Captured'], 'date_approved' => now()]);
        $revision = $this->refresh();
        $change = $revision->changes()->where('rule_key', 'gene.add')->sole();
        $value = array_reverse($change->after_value, true);
        $value['plan'] = array_reverse($value['plan'], true);
        $change->update(['after_value' => $value]);
        $this->postJson($this->url($revision, 'gene.add'))->assertOk();
        $this->assertSoftDeleted($added);
    }

    public function test_approval_date_restores_to_captured_value_and_closes_revision(): void
    {
        $this->gene->update(['date_approved' => '2025-01-01']);
        $this->baseline->latestSnapshot->update(['snapshot' => SnapshotBuild::run($this->panel->group->fresh())]);
        $this->gene->update(['date_approved' => '2026-01-01']);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'gene.update', 'date_approved'))->assertOk()
            ->assertJsonPath('has_active_revision', false);
        $this->assertSame('2025-01-01', $this->gene->fresh()->date_approved->toDateString());
    }

    public function test_revisions_requested_recalculates_major_to_minor_and_can_finalize(): void
    {
        $this->gene->update(['tier' => 2]);
        $this->panel->update(['scope_description' => 'Minor edit']);
        $revision = $this->refresh();
        $this->assertSame('2.0', $revision->version_label);
        $revision->update(['status' => 'revisions_requested']);
        $this->postJson($this->url($revision, 'gene.update_tier'))->assertOk()
            ->assertJsonPath('active_revision.version_label', '1.1')
            ->assertJsonPath('active_revision.summary.requires_submission', false)
            ->assertJsonPath('active_revision.summary.can_finalize_without_approval', true);
        $this->postJson('/api/groups/'.$this->panel->group->uuid.'/scope-of-work/revisions/'.$revision->uuid.'/finalize')->assertOk();
        $this->assertSame('approved', $revision->fresh()->status);
    }

    public function test_hard_deleted_or_moved_gene_cannot_be_recreated_or_restored(): void
    {
        $this->gene->delete();
        $revision = $this->refresh();
        $url = $this->url($revision, 'gene.remove');
        $other = ExpertPanel::factory()->create();
        $this->gene->update(['expert_panel_id' => $other->id]);
        $this->postJson($url)->assertStatus(409);
        $this->gene->forceDelete();
        $this->postJson($url)->assertStatus(409);
        $this->assertNull(Gene::withTrashed()->find($this->gene->id));
    }

    public function test_missing_baseline_field_is_not_restored_as_null(): void
    {
        $snapshot = $this->baseline->latestSnapshot;
        $data = $snapshot->snapshot;
        unset($data['scope_of_work']['scope_genes'][0]['tier']);
        $snapshot->update(['snapshot' => $data]);
        $this->gene->update(['tier' => 2]);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'gene.update_tier'))->assertUnprocessable();
        $this->assertSame(2, $this->gene->fresh()->tier);
    }
}
