<?php

namespace Tests\Feature\End2End\Groups\EvidenceSummaries;

use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\ScopeOfWork\RevisionRefresh;
use App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RemovedScopeGeneTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->actingAs($this->setupUserWithPerson(null, ['groups-manage', 'ep-applications-manage']));
        Notification::fake();
    }

    public static function panelTypes(): array { return [['vcep'], ['scvcep']]; }

    private function panel(string $type): ExpertPanel
    {
        $panel = ExpertPanel::factory()->create(['date_completed' => now(),
            'expert_panel_type_id' => config("expert_panels.types.{$type}.id")]);
        $panel->group->update(['group_type_id' => config("groups.types.{$type}.id")]);
        return $panel;
    }

    #[DataProvider('panelTypes')]
    public function test_removal_preserves_evidence_and_partial_discard_restores_association(string $type): void
    {
        $panel = $this->panel($type);
        $gene = $panel->genes()->create(['gene_symbol' => 'NTRK3', 'hgnc_id' => 1, 'mondo_id' => 'MONDO:1', 'disease_name' => 'Disease A']);
        $other = $panel->genes()->create(['gene_symbol' => 'NTRK3', 'hgnc_id' => 1, 'mondo_id' => 'MONDO:2', 'disease_name' => 'Disease B']);
        $summary = EvidenceSummary::create(['expert_panel_id' => $panel->id, 'gene_id' => $gene->id,
            'summary' => 'Preserved evidence', 'variant' => 'Variant A']);
        $before = $summary->fresh()->getAttributes();
        $this->assertSame($gene->id, $summary->fresh()->gene->id);
        $base = ScopeOfWorkVersion::create(['group_id' => $panel->group_id, 'expert_panel_id' => $panel->id,
            'major_version' => 1, 'minor_version' => 0, 'status' => 'approved']);
        $base->snapshots()->create(['snapshot_schema_version' => '1.0.0', 'snapshot' => SnapshotBuild::run($panel->group->fresh())]);
        $baseSnapshot = $base->latestSnapshot->snapshot;
        $url = '/api/groups/'.$panel->group->uuid.'/expert-panel';
        $this->getJson($url.'/genes')->assertOk()->assertJsonFragment(['id' => $gene->id, 'evidence_summaries_count' => 1]);
        $this->deleteJson($url.'/genes', ['ids' => [$gene->id]])->assertOk();
        $this->assertSoftDeleted($gene);
        $this->assertTrue($summary->fresh()->gene->trashed());
        $panel->update(['scope_description' => 'Another change']);
        $revision = RevisionRefresh::run($panel->group->fresh());
        $this->getJson($url.'/genes')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $other->id);
        $this->getJson($url.'/evidence-summaries')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.gene_id', $gene->id)->assertJsonPath('data.0.gene.disease_name', 'Disease A')
            ->assertJsonPath('data.0.gene_scope_status', 'pending_removal');
        $this->assertSame($before, $summary->fresh()->getAttributes());
        $change = $revision->changes()->where('rule_key', 'gene.remove')->sole();
        $this->postJson('/api/groups/'.$panel->group->uuid.'/scope-of-work/revisions/'.$revision->uuid.'/changes/'.$change->id.'/discard')->assertOk();
        $this->getJson($url.'/evidence-summaries')->assertOk()->assertJsonPath('data.0.gene.deleted_at', null)
            ->assertJsonPath('data.0.gene_scope_status', null);
        $this->assertSame($before, $summary->fresh()->getAttributes());
        $this->assertSame($baseSnapshot, $base->latestSnapshot->fresh()->snapshot);

        // The same stored evidence becomes historical when the removal is approved.
        $this->deleteJson($url.'/genes', ['ids' => [$gene->id]])->assertOk();
        $revision = RevisionRefresh::run($panel->group->fresh());
        $revision->update(['status' => 'approved', 'approved_at' => now()]);
        $this->getJson($url.'/evidence-summaries')->assertOk()->assertJsonPath('data.0.gene_scope_status', 'removed');
        $this->assertSame($before, $summary->fresh()->getAttributes());
    }

    #[DataProvider('panelTypes')]
    public function test_edit_preserves_removed_association_but_new_or_changed_associations_must_be_active(string $type): void
    {
        $panel = $this->panel($type);
        $gene = $panel->genes()->create(['gene_symbol' => 'OLD', 'hgnc_id' => 1]);
        $otherRemoved = $panel->genes()->create(['gene_symbol' => 'OTHER', 'hgnc_id' => 2]);
        $foreign = ExpertPanel::factory()->vcep()->create()->genes()->create(['gene_symbol' => 'FOREIGN', 'hgnc_id' => 3]);
        $summary = EvidenceSummary::create(['expert_panel_id' => $panel->id, 'gene_id' => $gene->id, 'summary' => 'Original', 'variant' => 'V']);
        $gene->delete();
        $otherRemoved->delete();
        $url = '/api/groups/'.$panel->group->uuid.'/expert-panel/evidence-summaries';
        $data = ['gene_id' => $gene->id, 'summary' => 'Edited content', 'variant' => 'V'];
        $this->putJson($url.'/'.$summary->id, $data)->assertOk();
        $this->assertSame($gene->id, $summary->fresh()->gene_id);
        $this->assertSame('Edited content', $summary->fresh()->summary);
        $untouched = ['summary' => 'Edited without replacement', 'variant' => 'New variant',
            'assertion_id' => 'New assertion', 'vci_url' => 'https://example.org/evidence'];
        $this->putJson($url.'/'.$summary->id, $untouched)->assertOk();
        $this->assertSame($gene->id, $summary->fresh()->gene_id);
        foreach ($untouched as $field => $value) $this->assertSame($value, $summary->fresh()->$field);
        $this->putJson($url.'/'.$summary->id, [...$untouched, 'gene_id' => null])->assertUnprocessable();
        foreach ([$gene, $otherRemoved, $foreign] as $invalid) {
            $this->postJson($url, array_replace($data, ['gene_id' => $invalid->id]))->assertUnprocessable();
        }
        foreach ([$otherRemoved, $foreign] as $invalid) {
            $this->putJson($url.'/'.$summary->id, array_replace($data, ['gene_id' => $invalid->id]))->assertUnprocessable();
        }
        $this->assertSame($gene->id, $summary->fresh()->gene_id);
        $this->assertSame(1, $panel->evidenceSummaries()->count());
        $active = $panel->genes()->create(['gene_symbol' => 'ACTIVE', 'hgnc_id' => 4]);
        $this->putJson($url.'/'.$summary->id, [...$untouched, 'gene_id' => $active->id])->assertOk();
        $this->assertSame($active->id, $summary->fresh()->gene_id);
    }

    public function test_gcep_gene_list_does_not_gain_evidence_behavior(): void
    {
        $panel = $this->panel('gcep');
        $gene = $panel->genes()->create(['gene_symbol' => 'GENE', 'hgnc_id' => 1]);
        $url = '/api/groups/'.$panel->group->uuid.'/expert-panel';
        $row = $this->getJson($url.'/genes')->assertOk()->json('0');
        $this->assertArrayNotHasKey('evidence_summaries_count', $row);
        $this->postJson($url.'/evidence-summaries', ['gene_id' => $gene->id, 'summary' => 'No', 'variant' => 'V'])->assertUnprocessable();
    }
}
