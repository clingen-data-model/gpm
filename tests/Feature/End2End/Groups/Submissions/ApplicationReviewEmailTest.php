<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Models\Comment;
use App\Models\Email;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Actions\ScopeOfWork\RevisionSubmit;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Database\Seeders\NextActionTypesTableSeeder;
use Database\Seeders\NextActionAssigneesTableSeeder;
use PHPUnit\Framework\Attributes\DataProvider;

class ApplicationReviewEmailTest extends TestCase
{
    private ExpertPanel $panel;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed([SubmissionTypeAndStatusSeeder::class, NextActionTypesTableSeeder::class, NextActionAssigneesTableSeeder::class, \Database\Seeders\CommentTypesSeeder::class]);
        $user = $this->setupUserWithPerson(null, ['ep-applications-manage', 'groups-manage']);
        $this->actingAs($user);
        $this->panel = ExpertPanel::factory()->vcep()->create(['current_step' => 1]);
        ContactAdd::run($this->panel->uuid, $user->person->uuid);
    }

    public static function contexts(): array
    {
        return [[false], [true]];
    }

    private function submission(bool $revision): Submission
    {
        $submission = Submission::factory()->create([
            'group_id' => $this->panel->group_id, 'submission_type_id' => 1,
            'submission_status_id' => config('submissions.statuses.pending.id'),
            'submitter_id' => auth()->user()->person->id,
        ]);
        if ($revision) {
            $this->panel->update(['date_completed' => now(), 'current_step' => 4]);
            $version = ScopeOfWorkVersion::create([
                'group_id' => $this->panel->group_id, 'expert_panel_id' => $this->panel->id,
                'major_version' => 2, 'minor_version' => 0, 'status' => 'submitted', 'submission_id' => $submission->id,
            ]);
            $version->changes()->create(['area' => 'scope', 'change_type' => 'updated', 'requires_approval' => 'yes', 'approval_step' => 1]);
            $submission->update(['scope_of_work_version_id' => $version->id,
                'data' => ['context' => 'scope_of_work_revision', 'approval_step' => 1, 'target_version' => '2.0']]);
        }
        return $submission;
    }

    private function url(Submission $submission, string $action): string
    {
        return '/api/groups/'.$this->panel->group->uuid.'/application/submission/'.$submission->id.'/'.$action;
    }

    private function feedback(): void
    {
        foreach (['required-revision' => 'Open required', 'suggestion' => 'Open suggestion', 'internal-comment' => 'Private internal'] as $type => $content) {
            foreach ([false, true] as $resolved) {
                Comment::factory()->create([
                    'subject_type' => get_class($this->panel->group), 'subject_id' => $this->panel->group_id,
                    'comment_type_id' => config('comments.types.'.$type.'.id'),
                    'content' => $resolved ? 'Resolved '.$content : $content,
                    'resolved_at' => $resolved ? now() : null,
                ]);
            }
        }
    }

    #[DataProvider('contexts')]
    public function test_drafts_select_only_open_external_comments_and_do_not_resolve_them(bool $revision): void
    {
        $this->submission($revision);
        $this->feedback();
        foreach ([$revision ? 'ScopeOfWorkRevisionRequestTemplate' : 'ApplicationRevisionRequestTemplate', 'InitialApprovalMailTemplate', 'SustainedCurationApprovalMailTemplate'] as $name) {
            $draft = $this->getJson('/api/email-drafts/groups/'.$this->panel->group->uuid.'?'.http_build_query([
                'templateClass' => 'App\\Mail\\UserDefinedMailTemplates\\'.$name,
            ]))->assertOk()->json();
            foreach (['Open required', 'Open suggestion'] as $text) $this->assertStringContainsString($text, $draft['body']);
            foreach (['Resolved', 'Private internal'] as $text) $this->assertStringNotContainsString($text, $draft['body']);
            if (str_contains($name, 'Request')) {
                $this->assertSame("Required Revisions:\n- Open required\n\nSuggestions:\n- Open suggestion", $draft['reviewer_note']);
            }
        }
        $this->assertSame(3, $this->panel->group->comments()->pending()->count());
    }

    public function test_step_four_and_resubmission_drafts_use_current_chair_feedback_for_note_and_email(): void
    {
        $this->feedback();
        foreach ([1, 2] as $type) {
            $old = $this->submission(false);
            $old->update(['submission_type_id' => $type, 'submission_status_id' => 2]);
            $old->judgements()->create(['person_id' => auth()->user()->person->id, 'decision' => 'request-revisions', 'notes' => 'Old chair feedback']);
            $current = $this->submission(false);
            $current->update(['submission_type_id' => $type]);
            $current->judgements()->create(['person_id' => auth()->user()->person->id, 'decision' => 'request-revisions', 'notes' => 'Current chair feedback']);
            $draft = $this->getJson('/api/email-drafts/groups/'.$this->panel->group->uuid.'?'.http_build_query([
                'templateClass' => 'App\\Mail\\UserDefinedMailTemplates\\ApplicationRevisionRequestTemplate',
            ]))->assertOk()->json();
            foreach (['reviewer_note', 'body'] as $field) {
                foreach (['Open required', 'Open suggestion', 'Current chair feedback'] as $text) $this->assertStringContainsString($text, $draft[$field]);
                foreach (['Old chair feedback', 'Private internal', 'Resolved'] as $text) $this->assertStringNotContainsString($text, $draft[$field]);
            }
        }
    }

    #[DataProvider('contexts')]
    public function test_request_revisions_archives_and_logs_edited_email_once_with_independent_note(bool $revision): void
    {
        $submission = $this->submission($revision);
        $this->postJson($this->url($submission, 'rejection'), [
            'notify_contacts' => true, 'subject' => 'Edited subject', 'body' => 'Edited outgoing email',
            'response_content' => 'Independent reviewer note',
        ])->assertOk();
        $this->assertSame('Independent reviewer note', $submission->fresh()->response_content);
        $this->assertSame(1, Email::where('subject', 'Edited subject')->count());
        $logs = $this->panel->group->logEntries()->where('activity_type', 'application-email-sent')->get();
        $this->assertCount(1, $logs);
        $this->assertSame('Edited outgoing email', $logs->first()->properties['body']);
        $this->assertStringNotContainsString('Edited outgoing email', $submission->fresh()->response_content);
    }

    public static function approvals(): array
    {
        return [[false, false, true], [false, true, true], [true, false, true], [true, true, true], [true, false, false]];
    }

    #[DataProvider('approvals')]
    public function test_approval_email_after_first_or_later_round(bool $revision, bool $resubmit, bool $notify): void
    {
        $submission = $this->submission($revision);
        $this->feedback();
        if ($resubmit) {
            $this->postJson($this->url($submission, 'rejection'), ['notify_contacts' => false, 'response_content' => 'Keep this note'])->assertOk();
            $submission = $revision
                ? app(RevisionSubmit::class)->handle($this->panel->group, $submission->scopeOfWorkVersion->fresh(), auth()->user()->person, 'Updated')->submission
                : app(ApplicationSubmitStep::class)->handle($this->panel->group, auth()->user()->person, 'Updated');
        }
        $before = $this->panel->fresh()->current_step;
        if ($revision) {
            $this->postJson($this->url($submission, 'scope-of-work/approve'), [
                'date_approved' => now()->toDateString(), 'notify_contacts' => $notify,
                'subject' => 'Approval edit', 'body' => 'Editable approval body',
            ])->assertOk();
            $this->assertSame($before, $this->panel->fresh()->current_step);
        } else {
            app(StepApprove::class)->handle($this->panel->fresh(), now(), $notify, 'Approval edit', 'Editable approval body');
        }
        $this->assertSame((int) $notify, Email::where('subject', 'Approval edit')->count());
        $this->assertSame((int) $notify, $this->panel->group->logEntries()->where('activity_type', 'application-email-sent')->count());
        $this->assertSame(3, $this->panel->group->comments()->pending()->count());
        if ($revision) {
            $this->postJson($this->url($submission, 'scope-of-work/approve'), [
                'date_approved' => now()->toDateString(), 'notify_contacts' => true,
                'subject' => 'Approval edit', 'body' => 'Duplicate email',
            ])->assertUnprocessable();
            $this->assertSame((int) $notify, Email::where('subject', 'Approval edit')->count());
            $this->assertSame((int) $notify, $this->panel->group->logEntries()->where('activity_type', 'application-email-sent')->count());
        }
    }

    public function test_step_four_request_keeps_note_independent_and_logs_email_once(): void
    {
        $submission = $this->submission(false);
        $submission->update(['submission_type_id' => config('submissions.types.application.sustained-curation.id')]);
        $this->panel->update(['current_step' => 4]);
        $this->postJson($this->url($submission, 'rejection'), [
            'notify_contacts' => true, 'subject' => 'Legacy subject', 'body' => 'Legacy outgoing body', 'response_content' => 'Step four reviewer note',
        ])->assertOk();
        $this->assertSame(1, Email::where('subject', 'Legacy subject')->count());
        $this->assertSame('Step four reviewer note', $submission->fresh()->response_content);
        $this->getJson('/api/groups/'.$this->panel->group->uuid.'/application/review-history')->assertOk()
            ->assertJsonPath('cycles.0.rounds.0.revisions_requested_notes', 'Step four reviewer note');
        $this->assertSame(1, $this->panel->group->logEntries()->where('activity_type', 'application-email-sent')->count());
        $this->assertSame(1, $this->panel->group->logEntries()->where('description', 'like', '%Legacy outgoing body%')->count());
    }

    public function test_submission_and_resubmission_are_distinct_group_log_entries_in_same_minute(): void
    {
        $original = $this->submission(true);
        $version = $original->scopeOfWorkVersion;
        $original->delete();
        $version->update(['status' => 'draft', 'submission_id' => null]);
        $first = app(RevisionSubmit::class)->handle($this->panel->group, $version->fresh(), auth()->user()->person, 'First')->submission;
        $this->postJson($this->url($first, 'rejection'), ['notify_contacts' => false, 'response_content' => 'Revise'])->assertOk();
        $second = app(RevisionSubmit::class)->handle($this->panel->group, $version->fresh(), auth()->user()->person, 'Second')->submission;
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('Revise', $first->fresh()->response_content);
        $logs = $this->panel->group->logEntries()->where('activity_type', 'application-step-submitted')->get();
        $this->assertCount(2, $logs);
        $this->assertStringContainsString('Scope of Work revision 2.0 submitted for review', $logs->first()->description);
        $response = $this->getJson('/api/groups/'.$this->panel->group->uuid.'/activity-logs')->assertOk();
        $this->assertCount(2, collect($response->json('data'))->where('activity_type', 'application-step-submitted'));
        $this->assertSame(1, $first->applicationSnapshot()->count());
        $this->assertSame(1, $second->applicationSnapshot()->count());
    }
}
