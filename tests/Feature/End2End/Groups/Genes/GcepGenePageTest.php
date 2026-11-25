<?php

namespace Tests\Feature\End2End\Groups\Genes;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\GcepCaptureScopeGeneSnapshots;
use App\Services\Api\GtApiService;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GcepGenePageTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        Sanctum::actingAs($this->setupUser(permissions: ['ep-applications-manage']));
    }

    private function scope(int $count): ExpertPanel
    {
        $panel = ExpertPanel::factory()->gcep()->create();
        $panel->genes()->createMany(array_map(fn ($n) => ['hgnc_id' => $n, 'gene_symbol' => sprintf('GENE%04d', $n)], range(1, $count)));
        return $panel;
    }

    private function url(ExpertPanel $panel): string { return '/api/groups/'.$panel->group->uuid.'/expert-panel/genes'; }

    public static function sizes(): array { return [[20, 20], [100, 50], [367, 100], [1001, 20]]; }

    #[DataProvider('sizes')]
    public function test_paginates_genes_before_enrichment_and_preserves_full_list(int $count, int $size): void
    {
        $panel = $this->scope($count);
        $gt = $this->mock(GtApiService::class);
        $gt->shouldReceive('lookupGcepGenesComplete')->once()->withArgs(fn ($symbols) => count($symbols) === $size)->andReturnUsing(function ($symbols) {
            $rows = [];
            foreach (range(1, 5) as $n) $rows[] = ['gene_symbol' => $symbols[0], 'curation_id' => 'c'.$n, 'curation_status' => 'Status '.$n, 'expert_panel' => 'Panel '.$n];
            return ['data' => $rows];
        });
        $this->getJson($this->url($panel).'/gcep-page?per_page='.$size)->assertOk()
            ->assertJsonPath('total', $count)->assertJsonCount($size, 'data')
            ->assertJsonCount(5, 'data.0.details')->assertJsonCount(5, 'data.0.statuses')
            ->assertJsonPath('data.1.statuses', ['Not Curated']);
        $this->getJson($this->url($panel))->assertOk()->assertJsonCount($count);
    }

    public function test_failure_is_explicit_and_page_input_is_bounded(): void
    {
        $panel = $this->scope(20);
        $this->mock(GtApiService::class)->shouldReceive('lookupGcepGenesComplete')->once()->andThrow(new \RuntimeException('timeout'));
        $this->getJson($this->url($panel).'/gcep-page')->assertStatus(502)->assertJsonMissing(['Not Curated']);
        $this->getJson($this->url($panel).'/gcep-page?per_page=1000')->assertStatus(422);
        $this->getJson($this->url($panel).'/gcep-page?sort=statuses')->assertStatus(422);
    }

    public function test_membership_only_page_skips_gt_and_preserves_pagination_and_sort(): void
    {
        $panel = $this->scope(367);
        $this->mock(GtApiService::class)->shouldNotReceive('lookupGcepGenesComplete');
        $this->getJson($this->url($panel).'/gcep-page?enrich=0&page=2&per_page=20&direction=desc')
            ->assertOk()->assertJsonPath('total', 367)->assertJsonCount(20, 'data')
            ->assertJsonPath('data.0.gene_symbol', 'GENE0347')->assertJsonMissingPath('data.0.statuses');
        $this->getJson($this->url($panel).'/gcep-page?enrich=invalid')->assertStatus(422);
    }

    public function test_add_remove_and_tier_changes_affect_pages_and_validate_ownership(): void
    {
        Event::fake();
        $panel = $this->scope(20);
        $this->mock(GtApiService::class)->shouldReceive('lookupGcepGenesComplete')->andReturn(['data' => []]);
        $url = $this->url($panel);
        $gene = $panel->genes()->first();
        $this->putJson($url.'/update-tier', ['ids' => [$gene->id], 'tier' => 2])->assertOk();
        $this->getJson($url.'/gcep-page?sort=tier&direction=desc')->assertOk()->assertJsonPath('data.0.id', $gene->id);
        $other = $this->scope(1)->genes()->first();
        $this->putJson($url.'/update-tier', ['ids' => [$other->id], 'tier' => 1])->assertStatus(422);
        $this->postJson($url, ['genes' => [['hgnc_id' => 999, 'gene_symbol' => 'NEW']]])->assertOk();
        $this->getJson($url.'/gcep-page?page=2')->assertOk()->assertJsonPath('total', 21)->assertJsonCount(1, 'data');
        $this->deleteJson($url, ['ids' => [$gene->id]])->assertOk();
        $this->getJson($url.'/gcep-page')->assertOk()->assertJsonPath('total', 20);
    }

    public function test_snapshot_uses_all_scope_genes_and_keeps_prior_snapshot_on_failure(): void
    {
        $panel = $this->scope(367);
        $gt = $this->mock(GtApiService::class);
        $gt->shouldReceive('lookupGcepGenesComplete')->once()->withArgs(fn ($symbols) => count($symbols) === 367)->andReturnUsing(fn ($symbols) => ['data' => array_map(fn ($symbol) => ['gene_symbol' => $symbol, 'curation_id' => (string) \Illuminate\Support\Str::uuid()], $symbols)]);
        app(GcepCaptureScopeGeneSnapshots::class)->handle($panel->group);
        $this->assertDatabaseCount('scope_gene_snapshots', 367);
        $gt->shouldReceive('lookupGcepGenesComplete')->once()->andThrow(new \RuntimeException('timeout'));
        try { app(GcepCaptureScopeGeneSnapshots::class)->handle($panel->group); $this->fail('Expected failure'); }
        catch (\RuntimeException $error) { $this->assertSame('timeout', $error->getMessage()); }
        $this->assertDatabaseCount('scope_gene_snapshots', 367);
    }

    public function test_page_endpoint_does_not_apply_to_vceps_or_scvceps(): void
    {
        foreach (['vcep', 'scvcep'] as $type) {
            $panel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.'.$type.'.id')]);
            $panel->group->update(['group_type_id' => config('groups.types.'.$type.'.id')]);
            $this->getJson($this->url($panel).'/gcep-page')->assertNotFound();
            $this->getJson($this->url($panel))->assertOk()->assertExactJson([]);
        }
    }
}
