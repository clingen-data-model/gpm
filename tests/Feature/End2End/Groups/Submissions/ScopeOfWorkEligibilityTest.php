<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use App\Modules\ExpertPanel\Actions\ApplicationComplete;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Actions\ScopeOfWork\InitialVersionCreate;
use App\Modules\Group\Actions\ScopeOfWork\RevisionGuard;
use App\Modules\Group\Actions\ScopeOfWork\RevisionRefresh;
use App\Modules\Group\Actions\ScopeOfWork\StatusGet;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Illuminate\Support\Carbon;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScopeOfWorkEligibilityTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->actingAs($this->setupUserWithPerson(null, ['groups-manage', 'ep-applications-manage']));
        // Exercise completion listeners without the unrelated external publication payload.
        $this->mock(\App\Actions\EventPublish::class)->shouldReceive('handle')->andReturnNull();
    }

    private function panel(string $type, int $step = 1): ExpertPanel
    {
        $panel = ExpertPanel::factory()->create(['expert_panel_type_id' => config("expert_panels.types.$type.id"),
            'current_step' => $step, 'date_completed' => null]);
        $panel->group->update(['group_type_id' => config("groups.types.$type.id")]);
        return $panel->fresh();
    }

    private function statusUrl(ExpertPanel $panel): string
    {
        return '/api/groups/'.$panel->group->uuid.'/scope-of-work';
    }

    private function counts(): array
    {
        return array_map(fn ($table) => DB::table($table)->count(),
            ['scope_of_work_versions', 'scope_of_work_snapshots', 'scope_of_work_changes']);
    }

    public static function incompleteStates(): array
    {
        return [['gcep', 1], ['vcep', 1], ['vcep', 2], ['vcep', 4], ['scvcep', 1], ['scvcep', 4]];
    }

    #[DataProvider('incompleteStates')]
    public function test_incomplete_application_and_ordinary_edits_never_initialize(string $type, int $step): void
    {
        $panel = $this->panel($type, $step);
        for ($approved = 1; $approved < $step; $approved++) {
            $panel->update(["step_{$approved}_approval_date" => '2026-01-01']);
        }
        $before = $this->counts();
        $this->getJson($this->statusUrl($panel).'/status')->assertOk()->assertJsonPath('versioning_applicable', false)
            ->assertJsonPath('has_approved_version', false)->assertJsonPath('has_active_revision', false);
        $this->assertSame($before, $this->counts());
        foreach (['member', 'gene', 'scope', 'name'] as $edit) {
            match ($edit) {
                'member' => GroupMember::factory()->create(['group_id' => $panel->group_id]),
                'gene' => Gene::factory()->create(['expert_panel_id' => $panel->id]),
                'scope' => $panel->update(['scope_description' => 'Initial application edit']),
                'name' => $panel->group->update(['name' => 'Initial edited name']),
            };
            $this->postJson($this->statusUrl($panel).'/refresh')->assertOk()->assertJsonPath('versioning_applicable', false);
            $this->assertSame($before, $this->counts());
        }
        try {
            InitialVersionCreate::run($panel->group);
            $this->fail('Incomplete application accepted by initializer');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('scope_of_work', $exception->errors());
        }
        $this->assertSame($before, $this->counts());
    }

    public static function completedStates(): array
    {
        return [['gcep', 1], ['vcep', 4], ['scvcep', 4]];
    }

    #[DataProvider('completedStates')]
    public function test_final_approval_initializes_once_using_completion_timestamp(string $type, int $step): void
    {
        $panel = $this->panel($type, $step);
        for ($approved = 1; $approved < $step; $approved++) $panel->update(["step_{$approved}_approval_date" => '2026-01-01']);
        $completed = Carbon::parse('2026-02-01');
        app(StepApprove::class)->handle($panel, $completed);
        $version = ScopeOfWorkVersion::where('group_id', $panel->group_id)->sole();
        $this->assertSame('1.0', $version->version_label);
        $this->assertSame('approved', $version->status);
        $this->assertTrue($version->approved_at->equalTo($completed));
        $this->assertTrue($panel->fresh()->date_completed->equalTo($completed));
        $this->assertSame(1, $version->snapshots()->count());
        app(ApplicationComplete::class)->handle($panel, $completed->copy()->addDay());
        InitialVersionCreate::run($panel->group);
        $this->assertSame(1, ScopeOfWorkVersion::where('group_id', $panel->group_id)->count());
        $this->assertSame(1, $version->snapshots()->count());
        $before = $this->counts();
        $this->getJson($this->statusUrl($panel).'/status')->assertOk()->assertJsonPath('versioning_applicable', true);
        $this->assertSame($before, $this->counts());
    }

    public function test_non_final_completion_and_premature_completion_event_create_nothing(): void
    {
        $panel = $this->panel('scvcep', 2);
        $before = $this->counts();
        \Illuminate\Support\Facades\Event::fake([ApplicationCompleted::class]);
        app(ApplicationComplete::class)->handle($panel, now());
        \Illuminate\Support\Facades\Event::assertNotDispatched(ApplicationCompleted::class);
        $this->assertNull($panel->fresh()->date_completed);
        $this->assertSame($before, $this->counts());
        app(\App\Modules\Group\Actions\ScopeOfWork\InitialVersionCreateFromApplicationCompleted::class)
            ->asListener(new ApplicationCompleted($panel));
        $this->assertSame($before, $this->counts());
    }

    public function test_stale_loaded_completion_does_not_bypass_persisted_state(): void
    {
        $panel = $this->panel('gcep');
        $panel->update(['date_completed' => now()]);
        $group = $panel->group->load('expertPanel');
        $panel->update(['date_completed' => null]);
        $this->assertFalse(StatusGet::run($group)['versioning_applicable']);
        $this->assertNull(RevisionRefresh::run($group));
        $this->expectException(ValidationException::class);
        InitialVersionCreate::run($group);
    }

    public function test_invalid_existing_rows_are_hidden_unchanged_and_mutations_rejected(): void
    {
        $panel = $this->panel('scvcep');
        $baseline = ScopeOfWorkVersion::create(['group_id' => $panel->group_id, 'major_version' => 1, 'status' => 'approved']);
        $draft = ScopeOfWorkVersion::create(['group_id' => $panel->group_id, 'major_version' => 1,
            'minor_version' => 1, 'status' => 'draft', 'base_version_id' => $baseline->id]);
        $before = $this->counts();
        $this->getJson($this->statusUrl($panel).'/status')->assertOk()->assertJsonPath('versioning_applicable', false)
            ->assertJsonPath('approved_version', null)->assertJsonPath('active_revision', null);
        $this->postJson($this->statusUrl($panel).'/refresh')->assertOk()->assertJsonPath('has_active_revision', false);
        foreach (['submit', 'finalize', 'discard', 'approve', 'changes/999/discard'] as $action) {
            $this->postJson($this->statusUrl($panel).'/revisions/'.$draft->uuid.'/'.$action, ['notes' => 'Test'])
                ->assertUnprocessable()->assertJsonValidationErrors('scope_of_work');
        }
        $this->assertSame($before, $this->counts());
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('approved', $baseline->fresh()->status);
        $submission = Submission::factory()->create(['group_id' => $panel->group_id, 'scope_of_work_version_id' => $draft->id,
            'submission_type_id' => 1, 'data' => ['context' => 'scope_of_work_revision']]);
        $draft->update(['status' => 'submitted', 'submission_id' => $submission->id]);
        $url = '/api/groups/'.$panel->group->uuid.'/application/submission/'.$submission->id;
        $this->postJson($url.'/rejection', ['response_content' => 'Change'])->assertUnprocessable()->assertJsonValidationErrors('scope_of_work');
        $this->postJson($url.'/scope-of-work/approve', ['date_approved' => '2026-01-01'])->assertUnprocessable()->assertJsonValidationErrors('scope_of_work');
        // Invalid submitted rows must not block ordinary initial application edits either.
        app(RevisionGuard::class)->ensureNotUnderReview($panel->group);
        $this->assertSame('submitted', $draft->fresh()->status);
    }

    public function test_backfill_excludes_active_but_incomplete_panels(): void
    {
        $incomplete = $this->panel('scvcep');
        $complete = $this->panel('gcep');
        foreach ([$incomplete, $complete] as $panel) $panel->group->update(['group_status_id' => config('groups.statuses.active.id')]);
        $complete->update(['date_completed' => '2026-01-01']);
        foreach ([$incomplete, $complete] as $panel) {
            $this->artisan('scope-of-work:backfill-initial', ['--group-id' => $panel->group_id])->assertExitCode(0);
        }
        $this->assertSame(0, ScopeOfWorkVersion::where('group_id', $incomplete->group_id)->count());
        $this->assertSame(1, ScopeOfWorkVersion::where('group_id', $complete->group_id)->count());
    }
}
