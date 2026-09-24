<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Models\ApplicationSnapshot;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild;
use App\Modules\Group\Actions\ScopeOfWork\RevisionRefresh;
use App\Modules\Group\Events\MemberRoleAssigned;
use App\Modules\Group\Events\MemberRoleRemoved;
use App\Services\CoreMemberAttestation;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class ScopeOfWorkMemberDiscardTest extends TestCase
{
    private ExpertPanel $panel;
    private ScopeOfWorkVersion $baseline;
    private GroupMember $member;
    private GroupMember $similar;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->setupRoles(['chair', 'expert', 'core-approval-member'], 'group');
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->actingAs($this->setupUserWithPerson(null, ['groups-manage', 'ep-applications-manage']));
        $this->panel = ExpertPanel::factory()->vcep()->create(['scope_description' => 'Approved', 'date_completed' => now()]);
        $this->member = $this->createMember();
        $this->similar = $this->createMember();
        $this->member->roles()->sync([$this->role('chair'), $this->role('expert')]);
        $this->similar->roles()->sync([$this->role('expert')]);
        $this->baseline = ScopeOfWorkVersion::create(['group_id' => $this->panel->group_id,
            'expert_panel_id' => $this->panel->id, 'major_version' => 1, 'status' => 'approved']);
        $this->baseline->snapshots()->create(['snapshot_schema_version' => '1.0.0',
            'snapshot' => SnapshotBuild::run($this->panel->group->fresh())]);
    }

    private function role(string $name): int
    {
        return config('permission.models.role')::where('name', $name)->where('scope', 'group')->sole()->id;
    }

    private function createMember(): GroupMember
    {
        return $this->panel->group->members()->create([
            'person_id' => Person::factory()->create(['first_name' => 'Alex', 'last_name' => 'Smith'])->id,
            'notes' => 'Approved notes', 'start_date' => '2025-01-01',
        ]);
    }

    private function refresh(): ScopeOfWorkVersion
    {
        return RevisionRefresh::run($this->panel->group->fresh());
    }

    private function url(ScopeOfWorkVersion $revision, string $rule, ?string $role = null): string
    {
        $change = $revision->changes()->where('rule_key', $rule)->get()->first(function ($change) use ($role) {
            return !$role || ($change->after_value['role'] ?? $change->before_value['role'] ?? null) === $role;
        });
        $this->assertNotNull($change);
        return '/api/groups/'.$this->panel->group->uuid.'/scope-of-work/revisions/'.$revision->uuid.'/changes/'.$change->id.'/discard';
    }

    public function test_added_member_discard_preserves_person_other_groups_snapshots_and_unrelated_edits(): void
    {
        $added = $this->createMember();
        $other = ExpertPanel::factory()->create()->group->members()->create(['person_id' => $added->person_id]);
        $this->panel->update(['scope_description' => 'Unrelated']);
        $revision = $this->refresh();
        $submission = Submission::factory()->create(['group_id' => $this->panel->group_id,
            'scope_of_work_version_id' => $revision->id, 'submission_status_id' => config('submissions.statuses.revisions-requested.id')]);
        $history = ApplicationSnapshot::create(['group_id' => $this->panel->group_id, 'submission_id' => $submission->id,
            'version' => 1, 'snapshot' => ['membership' => $added->id]]);
        $approved = $this->baseline->latestSnapshot->snapshot;
        Notification::fake();
        $this->postJson($this->url($revision, 'member.add'))->assertOk()->assertJsonPath('active_revision.summary.total_changes', 1);
        $this->assertSoftDeleted($added);
        $this->assertNotNull(Person::find($added->person_id));
        $this->assertNotNull($other->fresh());
        $this->assertSame('Unrelated', $this->panel->fresh()->scope_description);
        $this->assertSame(['membership' => $added->id], $history->fresh()->snapshot);
        $this->assertSame($approved, $this->baseline->latestSnapshot->fresh()->snapshot);
        $this->assertDatabaseHas('activity_log', ['description' => 'Discarded Scope of Work membership change', 'subject_id' => $this->panel->group_id]);
        Notification::assertNothingSent();
    }

    public function test_removed_member_restores_exact_record_baseline_roles_and_retirement_state(): void
    {
        $this->member->update(['end_date' => '2025-06-01']);
        $this->baseline->latestSnapshot->update(['snapshot' => SnapshotBuild::run($this->panel->group->fresh())]);
        $this->member->update(['notes' => 'Edited then removed', 'end_date' => '2026-01-01']);
        $this->member->roles()->sync([$this->role('coordinator')]);
        $this->member->delete();
        $this->similar->update(['notes' => 'Unrelated']);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.remove'))->assertOk()->assertJsonPath('has_active_revision', false);
        $restored = GroupMember::findOrFail($this->member->id);
        $this->assertSame($this->member->person_id, $restored->person_id);
        $this->assertSame('2025-06-01', $restored->end_date->toDateString());
        $this->assertSame('Approved notes', $restored->notes);
        $this->assertEqualsCanonicalizing([$this->role('chair'), $this->role('expert')], $restored->roles->pluck('id')->all());
        $this->assertSame('Unrelated', $this->similar->fresh()->notes);
    }

    public function test_individual_role_restoration_preserves_other_roles_and_edits_without_side_effects(): void
    {
        $this->member->roles()->sync([$this->role('coordinator'), $this->role('expert'), $this->role('core-approval-member')]);
        $revision = $this->refresh();
        $this->member->update(['notes' => 'Latest notes', 'end_date' => '2026-01-01']);
        $this->mock(CoreMemberAttestation::class)->shouldNotReceive('handle');
        Notification::fake();
        Event::fake([MemberRoleAssigned::class, MemberRoleRemoved::class]);
        $this->postJson($this->url($revision, 'member.update_role', 'coordinator'))->assertOk();
        $this->assertEqualsCanonicalizing([$this->role('expert'), $this->role('core-approval-member')], $this->member->fresh()->roles->pluck('id')->all());
        $this->postJson($this->url($revision, 'member.remove_chair'))->assertOk();
        $this->assertEqualsCanonicalizing([$this->role('chair'), $this->role('expert'), $this->role('core-approval-member')], $this->member->fresh()->roles->pluck('id')->all());
        $this->assertSame('Latest notes', $this->member->fresh()->notes);
        $this->assertNotNull($this->member->fresh()->end_date);
        $this->assertSame([$this->role('expert')], $this->similar->fresh()->roles->pluck('id')->all());
        Notification::assertNothingSent();
        Event::assertNotDispatched(MemberRoleAssigned::class);
        Event::assertNotDispatched(MemberRoleRemoved::class);
    }

    public function test_retirement_discard_restores_only_end_date(): void
    {
        $this->member->update(['end_date' => '2026-01-01', 'notes' => 'Keep']);
        $this->member->roles()->syncWithoutDetaching([$this->role('coordinator')]);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.retire'))->assertOk()->assertJsonPath('active_revision.summary.total_changes', 1);
        $this->assertNull($this->member->fresh()->end_date);
        $this->assertSame('Keep', $this->member->fresh()->notes);
        $this->assertTrue($this->member->fresh()->roles->contains('id', $this->role('coordinator')));
    }

    public function test_unretirement_discard_restores_baseline_date_only(): void
    {
        $this->member->update(['end_date' => '2025-06-01']);
        $this->baseline->latestSnapshot->update(['snapshot' => SnapshotBuild::run($this->panel->group->fresh())]);
        $this->member->update(['end_date' => null, 'notes' => 'Keep']);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.unretire'))->assertOk()->assertJsonPath('has_active_revision', false);
        $this->assertSame('2025-06-01', $this->member->fresh()->end_date->toDateString());
        $this->assertSame('Keep', $this->member->fresh()->notes);
    }

    public function test_role_discard_recalculates_major_to_minor_and_requested_revision_can_finalize(): void
    {
        $this->member->roles()->detach($this->role('chair'));
        $this->panel->update(['scope_description' => 'Minor']);
        $revision = $this->refresh();
        $this->assertSame('2.0', $revision->version_label);
        $revision->update(['status' => 'revisions_requested']);
        $this->postJson($this->url($revision, 'member.remove_chair'))->assertOk()
            ->assertJsonPath('active_revision.version_label', '1.1')
            ->assertJsonPath('active_revision.summary.can_finalize_without_approval', true);
        $this->postJson('/api/groups/'.$this->panel->group->uuid.'/scope-of-work/revisions/'.$revision->uuid.'/finalize')->assertOk();
    }

    public function test_final_role_change_closes_revision(): void
    {
        $this->member->roles()->syncWithoutDetaching([$this->role('coordinator')]);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.update_role'))->assertOk()->assertJsonPath('has_active_revision', false);
        $this->assertSame('discarded', $revision->fresh()->status);
    }

    public function test_stale_id_fresh_value_mismatch_and_submitted_revision_are_conflicts(): void
    {
        $this->member->update(['end_date' => '2026-01-01']);
        $revision = $this->refresh();
        $stale = $this->url($revision, 'member.retire');
        $this->refresh();
        $this->postJson($stale)->assertStatus(409);
        $current = $this->url($revision, 'member.retire');
        $this->member->update(['end_date' => '2026-02-01']);
        $this->postJson($current)->assertStatus(409);
        $revision->update(['status' => 'submitted']);
        $this->postJson($current)->assertStatus(409);
        $this->assertSame('2026-02-01', $this->member->fresh()->end_date->toDateString());
    }

    public function test_authorization_uses_inverse_membership_operation_and_member_update_for_roles(): void
    {
        $added = $this->createMember();
        $revision = $this->refresh();
        $this->actingAs($this->setupUserWithPerson(null, ['ep-applications-manage']));
        $this->postJson($this->url($revision, 'member.add'))->assertForbidden();
        $added->delete();
        $this->member->delete();
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.remove'))->assertOk();
        $this->member->roles()->syncWithoutDetaching([$this->role('coordinator')]);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.update_role'))->assertForbidden();
        $this->member->update(['end_date' => '2026-01-01']);
        $revision = $this->refresh();
        $this->actingAs($this->setupUserWithPerson());
        $this->postJson($this->url($revision, 'member.retire'))->assertForbidden();
    }

    public function test_removed_member_conflict_and_hard_missing_record_are_rejected(): void
    {
        $this->member->delete();
        $revision = $this->refresh();
        $url = $this->url($revision, 'member.remove');
        $replacement = $this->panel->group->members()->create(['person_id' => $this->member->person_id]);
        $this->postJson($url)->assertStatus(409);
        $replacement->delete();
        $this->member->forceDelete();
        $this->postJson($url)->assertStatus(409);
        $this->assertNull(GroupMember::withTrashed()->find($this->member->id));
    }

    public function test_missing_baseline_date_and_legacy_identity_are_not_guessed(): void
    {
        $snapshot = $this->baseline->latestSnapshot;
        $data = $snapshot->snapshot;
        foreach ($data['scope_of_work']['members'] as &$member) {
            if ($member['id'] === $this->member->id) unset($member['end_date']);
        }
        unset($member);
        $snapshot->update(['snapshot' => $data]);
        $this->member->update(['end_date' => '2026-01-01']);
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.retire'))->assertStatus(409);
        $change = $revision->changes()->where('rule_key', 'member.retire')->sole();
        $change->update(['before_value' => ['end_date' => null], 'after_value' => ['end_date' => '2026-01-01']]);
        $this->postJson($this->url($revision, 'member.retire'))->assertStatus(409);
    }

    public function test_missing_baseline_person_identity_cannot_be_interpreted_as_a_new_member(): void
    {
        $snapshot = $this->baseline->latestSnapshot;
        $data = $snapshot->snapshot;
        unset($data['scope_of_work']['members'][0]['person_id']);
        $snapshot->update(['snapshot' => $data]);
        $this->createMember();
        $revision = $this->refresh();
        $this->postJson($this->url($revision, 'member.add'))->assertStatus(409);
        $this->assertSame(3, $this->panel->group->members()->count());
    }

    public function test_role_identity_cannot_silently_switch_to_another_role_with_the_same_name(): void
    {
        $roleId = $this->role('chair');
        $this->member->roles()->detach($roleId);
        $revision = $this->refresh();
        config('permission.models.role')::whereKey($roleId)->update(['name' => 'old-chair']);
        $replacement = $this->setupRoles('chair', 'group');
        $this->postJson($this->url($revision, 'member.remove_chair'))->assertStatus(409);
        $this->assertFalse($this->member->fresh()->roles->contains('id', $replacement->id));
        $this->assertFalse($this->member->fresh()->roles->contains('id', $roleId));
    }
}
