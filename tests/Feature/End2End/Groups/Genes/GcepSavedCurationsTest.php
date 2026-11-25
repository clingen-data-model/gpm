<?php

namespace Tests\Feature\End2End\Groups\Genes;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Services\Api\GtApiService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GcepSavedCurationsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        Sanctum::actingAs($this->setupUser(permissions: ['ep-applications-manage']));
    }

    public function test_approved_scope_uses_all_saved_records_and_current_membership_without_gt(): void
    {
        $panel = ExpertPanel::factory()->gcep()->create(['step_1_approval_date' => '2025-01-01']);
        $genes = $panel->genes()->createMany([
            ['gene_symbol' => 'A', 'hgnc_id' => 1, 'tier' => 2],
            ['gene_symbol' => 'B', 'hgnc_id' => 2, 'tier' => 1],
        ]);
        foreach (['Published', 'Uploaded'] as $status) {
            $genes[0]->snapshots()->create([
                'curation_uuid' => (string) \Illuminate\Support\Str::uuid(),
                'captured_at' => '2024-12-20 10:00:00',
                'payload' => ['curation_status' => $status, 'expert_panel' => 'Saved panel', 'disease_name' => 'Saved disease'],
            ]);
        }
        $this->mock(GtApiService::class)->shouldNotReceive('lookupGcepGenesComplete');
        $url = '/api/groups/'.$panel->group->uuid.'/expert-panel/genes';
        $this->getJson($url)->assertOk()->assertJsonCount(2)
            ->assertJsonPath('0.statuses', ['Published', 'Uploaded'])->assertJsonCount(2, '0.details')
            ->assertJsonPath('0.tier', 2)->assertJsonPath('0.curation_source', 'snapshot')
            ->assertJsonPath('0.snapshot_captured_at.0', '2024-12-20T10:00:00.000000Z')
            ->assertJsonPath('1.statuses', ['No curation snapshot available'])->assertJsonPath('1.details', []);
        $this->getJson($url.'/gcep-page')->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.statuses', ['Published', 'Uploaded']);
        $this->assertDatabaseCount('scope_gene_snapshots', 2);
    }

    public function test_active_group_without_application_approval_still_uses_live_page_lookup(): void
    {
        $panel = ExpertPanel::factory()->gcep()->create(['step_1_approval_date' => null]);
        $panel->group->update(['group_status_id' => config('groups.statuses.active.id')]);
        $panel->genes()->create(['gene_symbol' => 'A', 'hgnc_id' => 1]);
        $this->mock(GtApiService::class)->shouldReceive('lookupGcepGenesComplete')->once()->with(['A'])->andReturn(['data' => []]);
        $this->getJson('/api/groups/'.$panel->group->uuid.'/expert-panel/genes/gcep-page')->assertOk()
            ->assertJsonPath('data.0.statuses', ['Not Curated'])->assertJsonMissingPath('data.0.curation_source');
    }
}
