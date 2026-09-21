<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild;
use App\Modules\Group\Actions\ScopeOfWork\RevisionRefresh;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use PHPUnit\Framework\Attributes\Test;

class ScopeOfWorkPartialDiscardTest extends TestCase
{
    private ExpertPanel $panel;
    private ScopeOfWorkVersion $baseline;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->actingAs($this->setupUserWithPerson(null, ['groups-manage', 'ep-applications-manage']));
        $this->panel = ExpertPanel::factory()->create([
            'long_base_name' => 'Approved long', 'short_base_name' => 'Approved short', 'scope_description' => 'Approved scope',
        ]);
        $this->panel->group->update(['name' => 'Approved name']);
        $this->baseline = ScopeOfWorkVersion::create([
            'group_id' => $this->panel->group_id, 'expert_panel_id' => $this->panel->id,
            'major_version' => 1, 'minor_version' => 0, 'status' => 'approved',
        ]);
        $this->baseline->snapshots()->create(['snapshot_schema_version' => '1.0.0',
            'snapshot' => SnapshotBuild::run($this->panel->group->fresh())]);
    }

    private function draft(): ScopeOfWorkVersion
    {
        $this->panel->group->update(['name' => 'Draft name']);
        $this->panel->update(['long_base_name' => 'Draft long', 'short_base_name' => 'Draft short', 'scope_description' => 'Draft scope']);
        return RevisionRefresh::run($this->panel->group->fresh());
    }

    private function url(ScopeOfWorkVersion $revision, int $id): string
    {
        return '/api/groups/'.$this->panel->group->uuid.'/scope-of-work/revisions/'.$revision->uuid.'/changes/'.$id.'/discard';
    }

    private function changeId(ScopeOfWorkVersion $revision, string $rule): int
    {
        return $revision->changes()->where('rule_key', $rule)->sole()->id;
    }

    #[Test]
    public function discard_rename_keeps_scope_and_reclassifies_to_minor(): void
    {
        $revision = $this->draft();
        $this->assertSame('2.0', $revision->version_label);
        $this->postJson($this->url($revision, $this->changeId($revision, 'panel_name.rename')))->assertOk()
            ->assertJsonPath('active_revision.version_label', '1.1')
            ->assertJsonPath('active_revision.summary.requires_submission', false)
            ->assertJsonPath('active_revision.summary.can_finalize_without_approval', true)
            ->assertJsonPath('active_revision.summary.total_changes', 1);
        $this->assertSame('Approved name', $this->panel->group->fresh()->name);
        $this->assertSame('Approved long', $this->panel->fresh()->long_base_name);
        $this->assertSame('Approved short', $this->panel->fresh()->short_base_name);
        $this->assertSame('Draft scope', $this->panel->fresh()->scope_description);
    }

    #[Test]
    public function discard_scope_keeps_rename_then_final_change_closes_revision(): void
    {
        $revision = $this->draft();
        $this->postJson($this->url($revision, $this->changeId($revision, 'scope_description.update')))->assertOk()
            ->assertJsonPath('active_revision.version_label', '2.0')
            ->assertJsonPath('active_revision.summary.requires_submission', true);
        $this->assertSame('Draft name', $this->panel->group->fresh()->name);
        $this->assertSame('Draft short', $this->panel->fresh()->short_base_name);
        $this->assertSame('Approved scope', $this->panel->fresh()->scope_description);
        $this->postJson($this->url($revision, $this->changeId($revision, 'panel_name.rename')))->assertOk()
            ->assertJsonPath('has_active_revision', false)->assertJsonPath('active_revision', null);
        $this->assertSame('discarded', $revision->fresh()->status);
        $this->assertSame(0, $revision->changes()->count());
        $this->assertSame(0, $revision->snapshots()->count());
    }

    #[Test]
    public function stale_id_and_submitted_revision_are_rejected(): void
    {
        $revision = $this->draft();
        $stale = $this->changeId($revision, 'panel_name.rename');
        RevisionRefresh::run($this->panel->group->fresh());
        $this->postJson($this->url($revision, $stale))->assertStatus(409);
        $id = $this->changeId($revision, 'panel_name.rename');
        $revision->update(['status' => 'submitted']);
        $this->postJson($this->url($revision, $id))->assertStatus(409);
        $this->assertSame('Draft name', $this->panel->group->fresh()->name);
    }

    #[Test]
    public function revisions_requested_minor_only_can_finalize_but_pending_submission_blocks_it(): void
    {
        $revision = $this->draft();
        $revision->update(['status' => 'revisions_requested']);
        $this->postJson($this->url($revision, $this->changeId($revision, 'panel_name.rename')))->assertOk()
            ->assertJsonPath('active_revision.status', 'revisions_requested')
            ->assertJsonPath('active_revision.summary.can_finalize_without_approval', true);
        $submission = Submission::factory()->create([
            'group_id' => $this->panel->group_id, 'scope_of_work_version_id' => $revision->id,
            'submission_status_id' => config('submissions.statuses.under-chair-review.id'),
        ]);
        $url = '/api/groups/'.$this->panel->group->uuid.'/scope-of-work/revisions/'.$revision->uuid.'/finalize';
        $this->postJson($url)->assertUnprocessable();
        $submission->update(['submission_status_id' => config('submissions.statuses.revisions-requested.id')]);
        $this->postJson($url)->assertOk();
        $this->assertSame('approved', $revision->fresh()->status);
        $this->assertSame('1.1', $revision->fresh()->version_label);
    }

    #[Test]
    public function authorization_matches_each_underlying_edit_permission(): void
    {
        $revision = $this->draft();
        $nameId = $this->changeId($revision, 'panel_name.rename');
        $scopeId = $this->changeId($revision, 'scope_description.update');
        $this->actingAs($this->setupUserWithPerson());
        $this->postJson($this->url($revision, $scopeId))->assertForbidden();
        $this->postJson($this->url($revision, $nameId))->assertForbidden();
        $this->actingAs($this->setupUserWithPerson(null, ['ep-applications-manage']));
        $this->postJson($this->url($revision, $nameId))->assertForbidden();
        $this->postJson($this->url($revision, $scopeId))->assertOk();
    }

    #[Test]
    public function explicit_null_is_restored_but_missing_baseline_field_is_rejected(): void
    {
        $snapshot = $this->baseline->latestSnapshot;
        $data = $snapshot->snapshot;
        $data['scope_of_work']['scope_description'] = null;
        $snapshot->update(['snapshot' => $data]);
        $revision = $this->draft();
        $this->postJson($this->url($revision, $this->changeId($revision, 'scope_description.update')))->assertOk();
        $this->assertNull($this->panel->fresh()->scope_description);
        unset($data['expert_panel']['short_base_name']);
        $snapshot->update(['snapshot' => $data]);
        $this->postJson($this->url($revision, $this->changeId($revision, 'panel_name.rename')))->assertUnprocessable();
        $this->assertSame('Draft name', $this->panel->group->fresh()->name);
    }

    #[Test]
    public function changed_saved_value_without_refresh_is_rejected_and_short_name_only_is_tracked(): void
    {
        $this->panel->update(['short_base_name' => 'New short']);
        $revision = RevisionRefresh::run($this->panel->group->fresh());
        $id = $this->changeId($revision, 'panel_name.rename');
        $this->panel->update(['short_base_name' => 'Even newer short']);
        $this->postJson($this->url($revision, $id))->assertStatus(409);
        $this->assertSame('Even newer short', $this->panel->fresh()->short_base_name);
    }
}
