<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use App\Modules\Group\Actions\ApplicationSubmissionReject;
use App\Modules\Group\Actions\SubmissionApprove;
use App\Modules\Group\Actions\ScopeOfWork\RevisionApprove;
use App\Modules\Group\Actions\ScopeOfWork\RevisionApproveFromSubmission;
use App\Modules\Group\Actions\ScopeOfWork\RevisionSubmissionApprove;
use App\Modules\Group\Actions\ScopeOfWork\RevisionSubmit;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Events\ScopeOfWorkReviewCompleted;
use Database\Seeders\NextActionTypesTableSeeder;
use Database\Seeders\NextActionAssigneesTableSeeder;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ScopeOfWorkWorkflowTest extends TestCase
{
    private ExpertPanel $panel;
    private ScopeOfWorkVersion $revision;
    private Submission $submission;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed([NextActionTypesTableSeeder::class, NextActionAssigneesTableSeeder::class, SubmissionTypeAndStatusSeeder::class]);
        $this->panel = ExpertPanel::factory()->create(['current_step' => 1]);
        $user = $this->setupUserWithPerson(null, ['ep-applications-manage']);
        $this->actingAs($user);
        $this->revision = ScopeOfWorkVersion::create([
            'group_id' => $this->panel->group_id,
            'expert_panel_id' => $this->panel->id,
            'major_version' => 2, 'minor_version' => 0, 'status' => 'submitted',
        ]);
        $this->submission = Submission::factory()->create([
            'group_id' => $this->panel->group_id,
            'submitter_id' => $user->person->id,
            'submission_type_id' => config('submissions.types.application.definition.id'),
            'submission_status_id' => config('submissions.statuses.pending.id'),
            'scope_of_work_version_id' => $this->revision->id,
            'data' => ['context' => 'scope_of_work_revision', 'approval_step' => 1],
        ]);
        $this->revision->update(['submission_id' => $this->submission->id]);
    }

    public static function approvalPaths(): array
    {
        return [['version'], ['submission'], ['generic'], ['helper'], ['version_http'], ['submission_http']];
    }

    #[Test]
    public function submission_snapshot_captures_scope_genes_people_and_roles(): void
    {
        $group = $this->panel->group;
        $group->update(['name' => 'Submitted group name', 'description' => 'Submitted description']);
        $panelFields = [
            'long_base_name' => 'Submitted long name',
            'short_base_name' => 'Submitted short name',
            'scope_description' => 'Submitted scope',
            'membership_description' => 'Submitted membership',
        ];
        $this->panel->update($panelFields);
        $gene = \App\Modules\ExpertPanel\Models\Gene::factory()->create([
            'expert_panel_id' => $this->panel->id, 'gene_symbol' => 'BRCA1',
        ]);
        $member = \App\Modules\Group\Models\GroupMember::factory()->create(['group_id' => $group->id]);
        $role = config('permission.models.role')::factory()->create(['scope' => 'group']);
        $member->roles()->attach($role->id);

        // Start without loaded relations, as on a fresh submission request.
        $group = $group->fresh()->unsetRelations();
        app(\App\Modules\Group\Actions\ApplicationSnapshotCreate::class)->asListener(
            new ApplicationStepSubmitted($group, $this->submission)
        );
        $applicationSnapshot = $this->submission->applicationSnapshot()->sole();
        $snapshot = $applicationSnapshot->snapshot;
        $this->assertSame('Submitted group name', data_get($snapshot, 'attributes.name'));
        $this->assertSame('Submitted description', data_get($snapshot, 'attributes.description'));
        foreach ($panelFields as $field => $value) {
            $this->assertSame($value, data_get($snapshot, "relations.expertPanel.attributes.$field"));
        }
        $capturedGene = collect(data_get($snapshot, 'relations.expertPanel.relations.genes'))
            ->firstWhere('attributes.id', $gene->id);
        $this->assertSame('BRCA1', data_get($capturedGene, 'attributes.gene_symbol'));
        $capturedMember = collect(data_get($snapshot, 'relations.members'))
            ->firstWhere('attributes.id', $member->id);
        $this->assertSame($member->person_id, data_get($capturedMember, 'relations.person.attributes.id'));
        $this->assertSame($member->person->first_name, data_get($capturedMember, 'relations.person.attributes.first_name'));
        $this->assertSame($role->name, data_get($capturedMember, 'relations.roles.0.attributes.name'));
        $this->assertSame($applicationSnapshot->id, data_get($this->submission->fresh()->data, 'application_snapshot_id'));
        $this->assertSame('scope_of_work_revision', data_get($this->submission->fresh()->data, 'context'));

        $gene->update(['gene_symbol' => 'Changed later']);
        $member->roles()->detach();
        $this->assertSame($snapshot, $applicationSnapshot->fresh()->snapshot);
    }

    #[Test]
    #[DataProvider('approvalPaths')]
    public function approval_paths_complete_actions_and_log_exactly_once_without_advancing_application(string $path): void
    {
        $before = $this->panel->fresh()->getAttributes();
        $actions = collect(['chair-review', 'review-submission'])->map(fn ($type) => NextAction::factory()->create([
            'expert_panel_id' => $this->panel->id,
            'type_id' => config("next_actions.types.$type.id"),
            'application_step' => 1, 'date_completed' => null,
        ]));
        $events = [];
        Event::listen(ScopeOfWorkReviewCompleted::class, function ($event) use (&$events) { $events[] = $event; });
        $approve = function () use ($path) {
            return match ($path) {
                'version' => app(RevisionApprove::class)->handle($this->panel->group, $this->revision),
                'submission' => app(RevisionSubmissionApprove::class)->handle($this->panel->group, $this->submission, now()),
                'generic' => app(SubmissionApprove::class)->handle($this->submission, now()),
                'helper' => app(RevisionApproveFromSubmission::class)->handle($this->submission, now()),
                'version_http' => $this->postJson('/api/groups/'.$this->panel->group->uuid.'/scope-of-work/revisions/'.$this->revision->uuid.'/approve'),
                'submission_http' => $this->postJson('/api/groups/'.$this->panel->group->uuid.'/application/submission/'.$this->submission->id.'/scope-of-work/approve', ['date_approved' => now()->toDateString()]),
            };
        };
        $result = $approve();
        if (str_ends_with($path, '_http')) {
            $result->assertOk();
        }
        $this->assertSame('approved', $this->revision->fresh()->status);
        $this->assertSame(config('submissions.statuses.approved.id'), $this->submission->fresh()->submission_status_id);
        foreach ($actions as $action) {
            $this->assertNotNull($action->fresh()->date_completed);
        }
        $this->assertApplicationProgressUnchanged($before);
        $this->assertLoggedActivity($this->panel->group, 'Scope of Work revision 2.0 was approved.');
        try {
            $result = $approve();
            if (str_ends_with($path, '_http')) {
                $result->assertUnprocessable();
                $this->assertCount(1, $events);
                return;
            }
            $this->fail('Repeated approval succeeded.');
        } catch (ValidationException $e) {
            $this->assertCount(1, $events);
        }
    }

    public static function invalidRounds(): array
    {
        return array_map(fn ($state) => [$state], ['missing', 'deleted', 'stale', 'approved', 'discarded', 'draft', 'revisions_requested', 'closed', 'inactive', 'wrong_group']);
    }

    #[Test]
    #[DataProvider('invalidRounds')]
    public function invalid_rounds_cannot_be_approved_or_sent_for_revisions(string $state): void
    {
        match ($state) {
            'missing' => $this->submission->update(['scope_of_work_version_id' => null]),
            'deleted' => $this->revision->delete(),
            'stale' => $this->revision->update(['submission_id' => null]),
            'closed' => $this->submission->update(['closed_at' => now()]),
            'inactive' => $this->submission->update(['submission_status_id' => config('submissions.statuses.revisions-requested.id')]),
            'wrong_group' => $this->revision->update(['group_id' => ExpertPanel::factory()->create()->group_id]),
            default => $this->revision->update(['status' => $state]),
        };
        Event::fake([ScopeOfWorkReviewCompleted::class]);
        $before = $this->submission->fresh()->getAttributes();
        foreach (['approve', 'reject'] as $operation) {
            try {
                if ($operation === 'approve') {
                    app(SubmissionApprove::class)->handle($this->submission, now());
                } else {
                    app(ApplicationSubmissionReject::class)->handle($this->panel->group, $this->submission, 'Reviewer note');
                }
                $this->fail('Invalid review round was accepted.');
            } catch (ValidationException $e) {
                $this->assertSame($before, $this->submission->fresh()->getAttributes());
            }
        }
        Event::assertNotDispatched(ScopeOfWorkReviewCompleted::class);
    }

    #[Test]
    public function revision_request_stores_reviewer_note_and_resubmit_creates_one_new_round(): void
    {
        $this->postJson('/api/groups/'.$this->panel->group->uuid.'/application/submission/'.$this->submission->id.'/rejection', [
            'notify_contacts' => false, 'body' => 'Email body', 'response_content' => 'Reviewer note',
        ])->assertOk();
        $this->assertSame('Reviewer note', $this->submission->fresh()->response_content);
        $this->assertSame('revisions_requested', $this->revision->fresh()->status);
        $this->postJson('/api/groups/'.$this->panel->group->uuid.'/application/submission/'.$this->submission->id.'/rejection', [
            'notify_contacts' => false, 'body' => 'Email body', 'response_content' => 'Overwritten note',
        ])->assertUnprocessable();
        $this->assertSame('Reviewer note', $this->submission->fresh()->response_content);

        $this->revision->changes()->create(['area' => 'scope', 'change_type' => 'updated', 'requires_approval' => 'yes', 'approval_step' => 1]);
        $staleRevision = $this->revision->fresh();
        Event::fake([ApplicationStepSubmitted::class]);
        $latest = app(RevisionSubmit::class)->handle($this->panel->group, $staleRevision, $this->submission->submitter, 'Resubmitted');
        $this->assertNotSame($this->submission->id, $latest->submission_id);
        $this->assertSame($this->revision->id, (int) $latest->submission->scope_of_work_version_id);
        $this->assertSame(2, $latest->submissions()->count());
        try {
            app(RevisionSubmit::class)->handle($this->panel->group, $staleRevision, $this->submission->submitter, 'Repeated');
            $this->fail('Duplicate submission succeeded.');
        } catch (ValidationException $e) {
            $this->assertSame(2, $latest->submissions()->count());
            $this->assertSame(1, $latest->submissions()->pending()->count());
        }
    }

    #[Test]
    public function normal_step_approval_cannot_consume_scope_of_work_submission(): void
    {
        $before = $this->panel->fresh()->getAttributes();
        try {
            app(StepApprove::class)->handle($this->panel, now());
            $this->fail('Normal step approval consumed a Scope of Work submission.');
        } catch (ValidationException $e) {
            $this->assertApplicationProgressUnchanged($before);
            $this->assertSame('submitted', $this->revision->fresh()->status);
        }
    }

    #[Test]
    public function direct_revision_approval_rejects_a_regular_submission_link(): void
    {
        $this->submission->update(['scope_of_work_version_id' => null, 'data' => null]);
        $this->expectException(ValidationException::class);
        try {
            app(RevisionApprove::class)->handle($this->panel->group, $this->revision);
        } finally {
            $this->assertSame('submitted', $this->revision->fresh()->status);
            $this->assertTrue($this->submission->fresh()->is_pending);
        }
    }

    #[Test]
    public function an_existing_active_round_blocks_submission_even_if_version_is_draft(): void
    {
        $this->revision->update(['status' => 'draft']);
        $this->revision->changes()->create(['area' => 'scope', 'change_type' => 'updated', 'requires_approval' => 'yes']);
        $this->expectException(ValidationException::class);
        try {
            app(RevisionSubmit::class)->handle($this->panel->group, $this->revision, $this->submission->submitter, 'Repeated');
        } finally {
            $this->assertSame(1, $this->revision->submissions()->count());
        }
    }

    #[Test]
    public function failure_in_review_completion_rolls_back_approval(): void
    {
        Event::listen(ScopeOfWorkReviewCompleted::class, function () { throw new \RuntimeException('Completion failed'); });
        $this->expectException(\RuntimeException::class);
        try {
            app(SubmissionApprove::class)->handle($this->submission, now());
        } finally {
            $this->assertSame('submitted', $this->revision->fresh()->status);
            $this->assertTrue($this->submission->fresh()->is_pending);
            $this->assertNull($this->submission->fresh()->closed_at);
            $this->assertDatabaseMissing('activity_log', ['description' => 'Scope of Work revision 2.0 was approved.']);
        }
    }

    #[Test]
    public function regular_submission_keeps_null_version_link_and_regular_approval(): void
    {
        $this->submission->delete();
        Event::fake([ApplicationStepSubmitted::class, ScopeOfWorkReviewCompleted::class]);
        $submission = app(ApplicationSubmitStep::class)->handle($this->panel->group, $this->submission->submitter, 'Application');
        $this->assertNull($submission->scope_of_work_version_id);
        app(SubmissionApprove::class)->handle($submission, now());
        $this->assertSame(config('submissions.statuses.approved.id'), $submission->fresh()->submission_status_id);
        $this->assertSame('submitted', $this->revision->fresh()->status);
        Event::assertNotDispatched(ScopeOfWorkReviewCompleted::class);
    }

    private function assertApplicationProgressUnchanged(array $before): void
    {
        $after = $this->panel->fresh()->getAttributes();

        foreach ([
            'current_step',
            'step_1_approval_date',
            'step_2_approval_date',
            'step_3_approval_date',
            'step_4_approval_date',
            'date_completed',
        ] as $field) {
            $this->assertSame(
                $before[$field] ?? null,
                $after[$field] ?? null,
                "Expert panel field [{$field}] changed."
            );
        }
    }
}
