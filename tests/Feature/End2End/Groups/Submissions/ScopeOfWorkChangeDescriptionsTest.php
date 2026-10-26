<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\ScopeOfWork\{SnapshotBuild, RevisionRefresh, HistoryGet, StatusGet};
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Tests\TestCase;

class ScopeOfWorkChangeDescriptionsTest extends TestCase
{
    public function test_description_context_uses_saved_snapshots_and_preserves_changes(): void
    {
        $this->setupForGroupTest();
        $this->actingAs($this->setupUserWithPerson(null, ['groups-manage', 'ep-applications-manage']));
        $panel = ExpertPanel::factory()->vcep()->create(['date_completed' => now()]);
        $gene = $panel->genes()->create(['gene_symbol' => 'NTRK3', 'hgnc_id' => 10,
            'mondo_id' => 'MONDO:1', 'disease_name' => 'Old disease', 'tier' => 1]);
        $baseline = ScopeOfWorkVersion::create(['group_id' => $panel->group_id,
            'expert_panel_id' => $panel->id, 'major_version' => 1, 'status' => 'approved']);
        $baseline->snapshots()->create(['snapshot_schema_version' => '1.0.0',
            'snapshot' => SnapshotBuild::run($panel->group->fresh())]);
        $gene->update(['disease_name' => 'New disease']);
        $revision = RevisionRefresh::run($panel->group->fresh());
        $stored = $revision->changes->toArray();
        // Unrefreshed live edits must not leak into the recorded revision description.
        $gene->update(['mondo_id' => 'MONDO:LIVE', 'disease_name' => 'Live disease']);
        $history = HistoryGet::run($panel->group->fresh())['versions'][0];
        $status = StatusGet::run($panel->group->fresh())['active_revision'];
        foreach ([$history, $status] as $payload) {
            $this->assertCount(1, $payload['changes']);
            $change = $payload['changes'][0];
            $this->assertSame('Old disease', $change['before_value']['disease_name']);
            $this->assertSame('New disease', $change['after_value']['disease_name']);
            $this->assertSame([
                'before' => ['gene_symbol' => 'NTRK3', 'mondo_id' => 'MONDO:1', 'disease_name' => 'Old disease'],
                'after' => ['gene_symbol' => 'NTRK3', 'mondo_id' => 'MONDO:1', 'disease_name' => 'New disease'],
            ], $change['display_gene']);
            $this->assertSame($stored[0]['requires_approval'], $change['requires_approval']);
            $this->assertSame($stored[0]['impact'], $change['impact']);
        }
        $this->assertSame($stored, $revision->fresh()->changes->toArray());
    }
}
