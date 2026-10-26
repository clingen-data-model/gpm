<?php

namespace Tests\Feature\End2End;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\ApplicationSubmissionAssignNextAction;
use App\Modules\Group\Actions\ApplicationSubmissionReject;
use App\Modules\Group\Actions\ApplicationSendToChairs;
use App\Modules\Group\Actions\ScopeOfWork\RevisionSubmit;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use Database\Seeders\NextActionAssigneesTableSeeder;
use Database\Seeders\NextActionTypesTableSeeder;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScopeOfWorkApplicationActivityTest extends TestCase
{
    private ExpertPanel $panel;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed([SubmissionTypeAndStatusSeeder::class, NextActionTypesTableSeeder::class, NextActionAssigneesTableSeeder::class]);
        $this->actingAs($this->setupUserWithPerson(null, ['ep-applications-manage']));
        $this->panel = ExpertPanel::factory()->create(['current_step' => 5, 'date_completed' => now(),
            'expert_panel_type_id' => config('expert_panels.types.scvcep.id')]);
        $this->panel->group->update(['group_type_id' => config('groups.types.scvcep.id')]);
    }

    private function revision(string $status = 'pending', int $step = 1): array
    {
        $revision = ScopeOfWorkVersion::create([
            'group_id' => $this->panel->group_id, 'expert_panel_id' => $this->panel->id,
            'major_version' => 2, 'minor_version' => 0,
            'status' => $status === 'revisions-requested' ? 'revisions_requested' : 'submitted',
        ]);
        $submission = Submission::factory()->create([
            'group_id' => $this->panel->group_id, 'scope_of_work_version_id' => $revision->id,
            'submitter_id' => auth()->user()->person->id,
            'submission_type_id' => config("submissions.types-by-step.{$step}.id"),
            'submission_status_id' => config("submissions.statuses.{$status}.id"),
            'closed_at' => $status === 'revisions-requested' ? now() : null,
            'data' => ['context' => 'scope_of_work_revision', 'approval_step' => $step, 'target_version' => '2.0'],
        ]);
        $revision->update(['submission_id' => $submission->id]);
        return [$revision, $submission];
    }

    public static function reviewStates(): array
    {
        return [['pending', 1], ['revisions-requested', 1], ['under-chair-review', 1],
            ['pending', 4], ['revisions-requested', 4], ['under-chair-review', 4]];
    }

    #[DataProvider('reviewStates')]
    public function test_active_revision_uses_current_round_and_preserves_initial_completion(string $status, int $step): void
    {
        [$revision, $submission] = $this->revision($status, $step);
        // A newer unrelated submission must not replace the revision's current round.
        Submission::factory()->create(['group_id' => $this->panel->group_id,
            'submission_type_id' => $step === 1 ? 2 : 1,
            'submission_status_id' => config('submissions.statuses.approved.id'),
            'created_at' => now()->addMinute()]);
        $before = $this->panel->fresh()->getAttributes();
        $this->getJson('/api/groups/applications')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.submission.id', $submission->id)
            ->assertJsonPath('data.0.submission.status.name', config("submissions.statuses.{$status}.name"))
            ->assertJsonPath('data.0.submission.data.approval_step', $step)
            ->assertJsonPath('data.0.submitted_step', 'Scope of Work Revision — '.config("submissions.types-by-step.{$step}.name"))
            ->assertJsonPath('data.0.last_submission_date', $submission->created_at->toISOString());
        $row = collect($this->getJson('/api/applications')->assertOk()->json('data'))->firstWhere('id', $this->panel->id);
        $this->assertSame($submission->id, $row['group']['active_scope_of_work_submission']['id']);
        $this->assertSame(config("submissions.statuses.{$status}.name"), $row['group']['active_scope_of_work_submission']['status']['name']);
        $this->assertEquals(5, $row['current_step']);
        $this->assertSame($before, $this->panel->fresh()->getAttributes());
    }

    public static function inactiveStates(): array
    {
        return array_map(fn ($state) => [$state], ['approved', 'discarded', 'draft', 'deleted', 'stale', 'closed']);
    }

    #[DataProvider('inactiveStates')]
    public function test_inactive_or_stale_revision_is_not_active_work(string $state): void
    {
        [$revision, $submission] = $this->revision();
        match ($state) {
            'deleted' => $revision->delete(),
            'stale' => $revision->update(['submission_id' => null]),
            'closed' => $submission->update(['closed_at' => now()]),
            default => $revision->update(['status' => $state]),
        };
        $this->getJson('/api/groups/applications')->assertOk()->assertJsonCount(0, 'data');
        $row = collect($this->getJson('/api/applications')->assertOk()->json('data'))->firstWhere('id', $this->panel->id);
        $this->assertNull($row['group']['active_scope_of_work_submission']);
    }

    public function test_completed_application_without_revision_is_not_active(): void
    {
        Submission::factory()->create(['group_id' => $this->panel->group_id, 'submission_status_id' => 1]);
        $this->getJson('/api/groups/applications')->assertOk()->assertJsonCount(0, 'data');
    }

    public static function visibility(): array
    {
        return [
            ['ep-applications-manage', ['pending', 'revisions-requested', 'under-chair-review']],
            ['ep-applications-comment', ['pending', 'under-chair-review']],
            ['ep-applications-approve', ['under-chair-review']],
            ['ep-applications-approve|ep-applications-manage', ['under-chair-review']],
        ];
    }

    #[DataProvider('visibility')]
    public function test_existing_role_visibility_applies_to_revision(string $permissions, array $visible): void
    {
        [$revision, $submission] = $this->revision();
        $this->actingAs($this->setupUserWithPerson(null, explode('|', $permissions)));
        foreach (['pending', 'revisions-requested', 'under-chair-review'] as $status) {
            $submission->update(['submission_status_id' => config("submissions.statuses.{$status}.id")]);
            $revision->update(['status' => $status === 'revisions-requested' ? 'revisions_requested' : 'submitted']);
            $this->getJson('/api/groups/applications')->assertOk()->assertJsonCount(in_array($status, $visible) ? 1 : 0, 'data');
        }
        $this->actingAs($this->setupUserWithPerson());
        $this->getJson('/api/groups/applications')->assertForbidden();
    }

    public function test_revision_next_actions_follow_review_and_resubmission(): void
    {
        Notification::fake();
        $this->setupPermission('ep-applications-approve');
        [$revision, $submission] = $this->revision('pending', 4);
        app(ApplicationSubmissionAssignNextAction::class)->asListener(new ApplicationStepSubmitted($this->panel->group, $submission));
        $review = $this->panel->nextActions()->pending()->sole();
        $this->assertSame('Review Scope of Work revision and respond to EP.', $review->entry);
        $this->assertSame(4, $review->application_step);
        $this->assertSame(config('next_actions.assignees.somatic-curation-core-group.id'), $review->assignee_id);
        app(ApplicationSendToChairs::class)->handle($this->panel->group, submissionId: $submission->id);
        $chair = $this->panel->nextActions()->pending()->ofType(config('next_actions.types.chair-review.id'))->sole();
        $this->assertSame(4, $chair->application_step);
        app(ApplicationSubmissionReject::class)->handle($this->panel->group, $submission->fresh(), 'Please revise.');
        $this->assertNotNull($chair->fresh()->date_completed);
        $this->assertNotNull($review->fresh()->date_completed);
        $revisionAction = $this->panel->nextActions()->pending()->sole();
        $this->assertSame('Update Scope of Work revision and resubmit for approval.', $revisionAction->entry);
        $this->assertSame(4, $revisionAction->application_step);
        $this->assertSame(config('next_actions.assignees.expert-panel.id'), $revisionAction->assignee_id);
        $row = collect($this->getJson('/api/applications')->assertOk()->json('data'))->firstWhere('id', $this->panel->id);
        $pending = collect($row['next_actions'])->whereNull('date_completed')->sole();
        $this->assertSame($revisionAction->id, $pending['id']);
        $this->assertSame($revisionAction->assignee_id, $pending['assigned_to']);
        $revision->changes()->create(['area' => 'scope', 'change_type' => 'updated', 'requires_approval' => 'yes', 'approval_step' => 4]);
        Event::fake([ApplicationStepSubmitted::class]);
        app(RevisionSubmit::class)->handle($this->panel->group, $revision->fresh(), $submission->submitter, 'Updated');
        $this->assertNotNull($revisionAction->fresh()->date_completed);
        $this->getJson('/api/groups/applications')->assertOk()
            ->assertJsonPath('data.0.submission.id', $revision->fresh()->submission_id)
            ->assertJsonPath('data.0.submission.status.name', 'Pending');
        app(\App\Modules\Group\Actions\ScopeOfWork\RevisionApprove::class)->handle($this->panel->group, $revision->fresh());
        $this->getJson('/api/groups/applications')->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(0, $this->panel->nextActions()->pending()->count());
    }
}
