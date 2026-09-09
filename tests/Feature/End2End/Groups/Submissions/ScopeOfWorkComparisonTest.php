<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use App\Models\ApplicationSnapshot;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use PHPUnit\Framework\Attributes\Test;

class ScopeOfWorkComparisonTest extends TestCase
{
    private ExpertPanel $panel;
    private ScopeOfWorkVersion $baseline;
    private ScopeOfWorkVersion $revision;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->actingAs($this->setupUserWithPerson(null, ['ep-applications-manage']));
        $this->panel = ExpertPanel::factory()->create();
        $this->baseline = ScopeOfWorkVersion::create([
            'group_id' => $this->panel->group_id, 'major_version' => 1, 'status' => 'approved',
        ]);
        $this->revision = ScopeOfWorkVersion::create([
            'group_id' => $this->panel->group_id, 'major_version' => 2, 'status' => 'approved',
            'base_version_id' => $this->baseline->id,
        ]);
    }

    private function submission(): Submission
    {
        return Submission::factory()->create([
            'group_id' => $this->panel->group_id,
            'scope_of_work_version_id' => $this->revision->id,
            'submission_status_id' => config('submissions.statuses.approved.id'),
            'data' => ['context' => 'scope_of_work_revision', 'base_version_id' => $this->baseline->id],
        ]);
    }

    private function snapshot(Submission $submission, array $data): ApplicationSnapshot
    {
        return ApplicationSnapshot::create([
            'group_id' => $submission->group_id, 'submission_id' => $submission->id,
            'version' => $submission->id, 'snapshot' => $data,
        ]);
    }

    private function data(string $name = 'Original'): array
    {
        return [
            'attributes' => ['name' => $name, 'description' => 'Description'],
            'relations' => [
                'expertPanel' => [
                    'attributes' => ['long_base_name' => 'Long', 'short_base_name' => 'Short',
                        'scope_description' => 'Scope', 'membership_description' => 'Membership'],
                    'relations' => ['genes' => [['attributes' => ['id' => 1, 'gene_symbol' => 'GENE1']]]],
                ],
                'members' => [[
                    'attributes' => ['id' => 10, 'person_id' => 20],
                    'relations' => [
                        'person' => ['attributes' => ['first_name' => 'Alex', 'last_name' => 'Smith']],
                        'roles' => [['attributes' => ['name' => 'chair']]],
                    ],
                ]],
            ],
        ];
    }

    private function url(Submission $submission): string
    {
        return '/api/groups/'.$this->panel->group->uuid.'/application/submission/'.$submission->id.'/scope-of-work/comparison';
    }

    #[Test]
    public function round_two_uses_previous_round_and_stays_independent_of_live_data(): void
    {
        $first = $this->submission();
        $before = $this->snapshot($first, $this->data());
        $second = $this->submission();
        $data = $this->data('Revised');
        $data['relations']['expertPanel']['relations']['genes'] = [['attributes' => ['id' => 2, 'gene_symbol' => 'GENE2']]];
        $data['relations']['members'][0]['relations']['roles'] = [['attributes' => ['name' => 'coordinator']]];
        $after = $this->snapshot($second, $data);
        $second->update(['data' => array_merge($second->data, ['application_snapshot_id' => $after->id])]);
        $this->panel->group->update(['name' => 'Live name must not appear']);
        $response = $this->getJson($this->url($second))->assertOk()
            ->assertJsonPath('status', 'complete')->assertJsonPath('mode', 'previous_review_round')
            ->assertJsonPath('before.snapshot_id', $before->id)->assertJsonPath('after.snapshot_id', $after->id)
            ->assertJsonPath('summary.changed_items', 5);
        $changes = collect($response->json('changes'));
        $this->assertSame('Original', $changes->firstWhere('section', 'group.name')['before']);
        $this->assertSame('Revised', $changes->firstWhere('section', 'group.name')['after']);
        $this->assertSame(['removed', 'added'], $changes->where('section', 'genes')->pluck('operation')->all());
        $this->assertSame(['removed', 'added'], $changes->where('section', 'member_roles')->pluck('operation')->all());
    }

    #[Test]
    public function first_round_uses_the_captured_baseline_version(): void
    {
        $data = $this->data();
        $baseline = $this->baseline->snapshots()->create([
            'snapshot_schema_version' => '1.0.0',
            'snapshot' => [
                'group' => $data['attributes'],
                'expert_panel' => $data['relations']['expertPanel']['attributes'],
                'scope_of_work' => ['scope_description' => 'Previous scope', 'membership_description' => 'Membership',
                    'scope_genes' => [['id' => 1, 'gene_symbol' => 'GENE1']],
                    'members' => [['person_id' => 20, 'first_name' => 'Alex', 'last_name' => 'Smith', 'roles' => [['name' => 'chair']]]]],
            ],
        ]);
        // A more recent approved version must not replace the recorded baseline.
        $this->revision->snapshots()->create(['snapshot_schema_version' => '1.0.0', 'snapshot' => []]);
        $submission = $this->submission();
        $this->snapshot($submission, $data);
        $this->getJson($this->url($submission))->assertOk()->assertJsonPath('status', 'complete')
            ->assertJsonPath('mode', 'approved_baseline')->assertJsonPath('before.snapshot_id', $baseline->id)
            ->assertJsonPath('before.scope_of_work_version_id', $this->baseline->id)
            ->assertJsonPath('changes.0.before', 'Previous scope')->assertJsonPath('changes.0.after', 'Scope');
    }

    #[Test]
    public function missing_relations_are_unavailable_not_empty_lists(): void
    {
        $first = $this->submission();
        $data = $this->data();
        unset($data['relations']['expertPanel']['relations']['genes'], $data['relations']['members']);
        $this->snapshot($first, $data);
        $second = $this->submission();
        $this->snapshot($second, $this->data());
        $this->getJson($this->url($second))->assertOk()->assertJsonPath('status', 'partial')
            ->assertJsonPath('unavailable_sections', ['genes', 'members'])
            ->assertJsonPath('rows.genes', null)->assertJsonPath('rows.members', null)
            ->assertJsonPath('summary.changed_items', 0)->assertJsonPath('changes', []);
    }

    #[Test]
    public function missing_previous_snapshot_does_not_fall_back_to_baseline(): void
    {
        $first = $this->submission();
        $second = $this->submission();
        $this->snapshot($second, $this->data());
        $this->getJson($this->url($second))->assertOk()->assertJsonPath('mode', 'previous_review_round')
            ->assertJsonPath('status', 'partial')->assertJsonPath('before.submission_id', $first->id)
            ->assertJsonPath('before.snapshot_id', null)->assertJsonCount(8, 'unavailable_sections');
    }

    #[Test]
    public function explicit_snapshot_pointer_cannot_read_another_submissions_snapshot(): void
    {
        $first = $this->submission();
        $snapshot = $this->snapshot($first, $this->data());
        $second = $this->submission();
        $second->update(['data' => array_merge($second->data, ['application_snapshot_id' => $snapshot->id])]);
        $this->getJson($this->url($second))->assertOk()->assertJsonPath('status', 'partial')
            ->assertJsonPath('after.snapshot_id', null)->assertJsonPath('changes', []);
    }

    #[Test]
    public function member_additions_and_removals_have_stored_labels(): void
    {
        $first = $this->submission();
        $this->snapshot($first, $this->data());
        $second = $this->submission();
        $data = $this->data();
        $data['relations']['members'][0]['attributes']['person_id'] = 21;
        $data['relations']['members'][0]['relations']['person']['attributes']['first_name'] = 'Robin';
        $this->snapshot($second, $data);
        $this->getJson($this->url($second))->assertOk()->assertJsonPath('status', 'complete')
            ->assertJsonPath('summary.changed_items', 2)
            ->assertJsonPath('changes.0.section', 'members')->assertJsonPath('changes.0.operation', 'removed')
            ->assertJsonPath('changes.0.label', 'Alex Smith')
            ->assertJsonPath('changes.1.operation', 'added')->assertJsonPath('changes.1.label', 'Robin Smith');
    }

    #[Test]
    public function missing_roles_do_not_look_like_removed_roles(): void
    {
        $first = $this->submission();
        $this->snapshot($first, $this->data());
        $second = $this->submission();
        $data = $this->data();
        unset($data['relations']['members'][0]['relations']['roles']);
        $this->snapshot($second, $data);
        $this->getJson($this->url($second))->assertOk()->assertJsonPath('status', 'partial')
            ->assertJsonPath('unavailable_sections', ['member_roles:20'])->assertJsonPath('changes', []);
    }

    #[Test]
    public function combined_rows_include_unchanged_added_removed_and_role_changes(): void
    {
        $first = $this->submission();
        $before = $this->data();
        $gene = fn ($id, $symbol) => ['attributes' => ['id' => $id, 'gene_symbol' => $symbol]];
        $before['relations']['expertPanel']['relations']['genes'] = [$gene(1, 'Same'), $gene(2, 'Removed'), $gene(4, 'Old')];
        $member = $before['relations']['members'][0];
        $unchanged = $member;
        $unchanged['attributes']['person_id'] = 30;
        $removed = $member;
        $removed['attributes']['person_id'] = 40;
        $before['relations']['members'] = [$member, $unchanged, $removed];
        $this->snapshot($first, $before);
        $second = $this->submission();
        $after = $before;
        $after['relations']['expertPanel']['relations']['genes'] = [$gene(1, 'Same'), $gene(3, 'Added'), $gene(4, 'New')];
        $member['relations']['roles'] = [['attributes' => ['name' => 'coordinator']]];
        $added = $unchanged;
        $added['attributes']['person_id'] = 50;
        $after['relations']['members'] = [$member, $unchanged, $added];
        $this->snapshot($second, $after);
        $response = $this->getJson($this->url($second))->assertOk()->assertJsonPath('status', 'complete');
        $genes = collect($response->json('rows.genes'))->keyBy('key');
        $this->assertCount(4, $genes);
        $this->assertSame('unchanged', $genes[1]['operation']);
        $this->assertSame('removed', $genes[2]['operation']);
        $this->assertSame('Removed', $genes[2]['before']['gene_symbol']);
        $this->assertSame('added', $genes[3]['operation']);
        $this->assertSame('changed', $genes[4]['operation']);
        $members = collect($response->json('rows.members'))->keyBy('key');
        $this->assertCount(4, $members);
        $this->assertSame('changed', $members[20]['operation']);
        $this->assertSame(['removed', 'added'], array_column($members[20]['roles'], 'operation'));
        $this->assertSame(['chair', 'coordinator'], array_column($members[20]['roles'], 'key'));
        $this->assertSame('unchanged', $members[30]['operation']);
        $this->assertSame('unchanged', $members[30]['roles'][0]['operation']);
        $this->assertSame('removed', $members[40]['operation']);
        $this->assertSame('added', $members[50]['operation']);
    }

    #[Test]
    public function cross_group_submission_is_not_found(): void
    {
        $submission = $this->submission();
        $submission->update(['group_id' => ExpertPanel::factory()->create()->group_id]);
        $this->getJson($this->url($submission))->assertNotFound();
    }

    #[Test]
    public function regular_submission_is_rejected(): void
    {
        $submission = $this->submission();
        $submission->update(['data' => ['context' => 'application_submission'], 'scope_of_work_version_id' => null]);
        $this->getJson($this->url($submission))->assertUnprocessable();
    }
}
