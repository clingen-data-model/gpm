<?php
namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;

class PublicationLinkTest extends TestCase
{
    private function publication(Group $group): Publication
    {
        return Publication::create(['group_id'=>$group->id, 'meta'=>['title'=>'Test publication'], 'source'=>'pmcid', 'identifier'=>'PMC'.random_int(100,999999)]);
    }

    public function test_authorized_link_changes_and_pair_validation(): void
    {
        $this->setupRoles('super-user');
        $user = $this->loginWithPerson();
        $user->assignRole('super-user');
        $group = Group::factory()->create();
        $other = Group::factory()->create();
        $a = $this->publication($group);
        $b = $this->publication($other);
        $c = $this->publication($group);
        $url = fn($p, $g) => '/api/groups/'.$g->uuid.'/publications/'.$p->id.'/link';
        $this->putJson($url($a,$group), ['published_publication_id'=>$a->id])->assertStatus(422);
        $this->putJson($url($a,$group), ['published_publication_id'=>99999999])->assertStatus(422);
        $this->putJson($url($a,$group), ['published_publication_id'=>$b->id])->assertNoContent();
        $this->assertSame($b->id, $a->fresh()->publishedPublication->id);
        $this->assertSame($a->id, $b->fresh()->preprint->id);
        $this->putJson($url($b,$other), ['published_publication_id'=>$a->id])->assertStatus(422);
        $this->putJson($url($b,$other), ['published_publication_id'=>$c->id])->assertStatus(422);
        $this->putJson($url($c,$group), ['published_publication_id'=>$b->id])->assertStatus(422);
        $this->putJson($url($a,$group), ['published_publication_id'=>$c->id])->assertNoContent();
        $this->assertNull($b->fresh()->preprint);
        $this->putJson($url($a,$group), ['published_publication_id'=>null])->assertNoContent();
        $this->assertNull($a->fresh()->published_publication_id);
        $this->putJson($url($a,$other), ['published_publication_id'=>$b->id])->assertNotFound();
        $this->putJson($url($a,$group), ['published_publication_id'=>$b->id])->assertNoContent();
        $this->deleteJson('/api/groups/'.$other->uuid.'/publications/'.$b->id)->assertNoContent();
        $this->assertNull($a->fresh()->published_publication_id);
        $user->removeRole('super-user');
        $this->putJson($url($a,$group), ['published_publication_id'=>$b->id])->assertForbidden();
    }

    public function test_search_is_case_insensitive_for_titles_and_identifiers(): void
    {
        $this->loginWithPerson();
        $group = Group::factory()->create();
        $a = $this->publication($group);
        $a->update(['meta' => ['title' => 'ClinGen study']]);
        $b = $this->publication($group);
        $b->update(['identifier' => '10.123/CLINGEN']);
        // Reproduce binary/case-sensitive comparison without changing local MySQL.
        \Illuminate\Support\Facades\DB::statement('PRAGMA case_sensitive_like = ON');
        try {
            foreach (['clingen', 'ClinGen', 'CLINGEN'] as $query) {
                $response = $this->getJson('/api/groups/'.$group->uuid.'/publications/search?query='.$query)->assertOk();
                $this->assertSame([$a->id, $b->id], array_column($response->json(), 'id'));
            }
        } finally {
            \Illuminate\Support\Facades\DB::statement('PRAGMA case_sensitive_like = OFF');
        }
    }

    public function test_both_sides_are_in_api_and_report_exports(): void
    {
        $this->setupRoles('super-user');
        $this->loginWithPerson()->assignRole('super-user');
        $group = Group::factory()->create();
        $other = Group::factory()->create();
        $a = $this->publication($group);
        $b = $this->publication($other);
        $a->update(['meta' => ['title' => 'Preprint title']]);
        $b->update(['meta' => ['title' => 'Published title', 'pmcid' => ['id' => 'PMC567']]]);
        $linkUrl = '/api/groups/'.$group->uuid.'/publications/'.$a->id.'/link';
        $this->putJson($linkUrl, ['published_publication_id' => $b->id])->assertNoContent();
        $this->getJson('/api/groups/'.$group->uuid.'/publications')->assertOk()->assertExactJson([]);
        $this->assertSame('Published title', $a->fresh()->publishedPublication->meta['title']);
        $this->assertSame('PMC567', $a->fresh()->publishedPublication->meta['pmcid']['id']);
        $this->getJson('/api/groups/'.$other->uuid.'/publications')->assertOk()
            ->assertJsonPath('0.preprint.id', $a->id);

        \Illuminate\Database\Eloquent\Model::preventLazyLoading();
        try {
            $rows = app(\App\Actions\ReportPublicationsMake::class)->handle();
        } finally {
            \Illuminate\Database\Eloquent\Model::preventLazyLoading(false);
        }
        $this->assertCount(2, $rows);
        $byId = collect($rows)->keyBy('publication_id');
        $this->assertSame('Published version', $byId[$a->uuid]['publication_relationship']);
        $this->assertSame('Published title', $byId[$a->uuid]['linked_publication_title']);
        $this->assertSame('PMC567', $byId[$a->uuid]['linked_publication_pmcid']);
        $this->assertSame('Linked preprint', $byId[$b->uuid]['publication_relationship']);
        $this->assertSame($a->identifier, $byId[$b->uuid]['linked_publication_pmcid']);
        $this->assertStringContainsString('publication='.$b->id, $byId[$a->uuid]['linked_publication_gpm_url']);
        $csv = $this->get('/api/report/publications')->assertOk()->streamedContent();
        $this->assertStringContainsString('linked_publication_title', $csv);
        $this->assertStringContainsString('Published version', $csv);
        $this->assertStringContainsString('Linked preprint', $csv);
        $this->assertStringContainsString('PMC567', $csv);
        $json = $this->getJson('/api/report/publications')->assertOk()->streamedContent();
        $this->assertSame('Published title', collect(json_decode($json, true))->keyBy('publication_id')[$a->uuid]['linked_publication_title']);

        // Also cover the existing filtered publication controller's JSON and CSV shapes.
        $controller = app(\App\Modules\Group\Http\Controllers\Api\PublicationReportController::class);
        $page = $controller->index(\Illuminate\Http\Request::create('/', 'GET', ['group_id' => $group->id]));
        $this->assertSame(1, $page->total());
        $this->assertSame($b->id, $page->items()[0]->publishedPublication->id);
        $export = $controller->index(\Illuminate\Http\Request::create('/', 'GET', ['group_id' => $group->id, 'csv' => true]));
        ob_start();
        $export->sendContent();
        $filteredCsv = ob_get_clean();
        $this->assertStringContainsString('Linked publication PMCID', $filteredCsv);
        $this->assertStringContainsString('PMC567', $filteredCsv);

        $this->putJson($linkUrl, ['published_publication_id' => null])->assertNoContent();
        $this->getJson('/api/groups/'.$group->uuid.'/publications')->assertJsonPath('0.published_publication', null);
        $this->getJson('/api/groups/'.$other->uuid.'/publications')->assertJsonPath('0.preprint', null);
        foreach (app(\App\Actions\ReportPublicationsMake::class)->handle() as $row) {
            $this->assertNull($row['publication_relationship']);
        }
    }

    public function test_group_list_hides_linked_preprints_but_annual_reviews_keep_them(): void
    {
        $this->setupRoles('super-user');
        $this->loginWithPerson()->assignRole('super-user');
        $group = Group::factory()->create();
        $preprint = $this->publication($group);
        $published = $this->publication($group);
        $unlinked = $this->publication($group);
        $url = '/api/groups/'.$group->uuid.'/publications';
        $linkUrl = $url.'/'.$preprint->id.'/link';

        $this->getJson($url)->assertOk()->assertJsonCount(3);
        $this->putJson($linkUrl, ['published_publication_id' => $published->id])->assertNoContent();
        $response = $this->getJson($url)->assertOk()->assertJsonCount(2);
        $this->assertSame([$unlinked->id, $published->id], array_column($response->json(), 'id'));
        $response->assertJsonPath('1.preprint.id', $preprint->id)
            ->assertJsonPath('1.preprint.meta.title', $preprint->meta['title']);

        $annual = $this->getJson($url.'?shape_exchange=true')->assertOk()->assertJsonCount(3);
        $this->assertContains($preprint->uuid, array_column($annual->json(), 'publication_id'));
        $this->assertCount(3, app(\App\Actions\ReportPublicationsMake::class)->handle());
        $this->assertSame(3, Publication::count());

        $this->putJson($linkUrl, ['published_publication_id' => null])->assertNoContent();
        $response = $this->getJson($url)->assertOk()->assertJsonCount(3);
        $this->assertContains($preprint->id, array_column($response->json(), 'id'));
        $response->assertJsonPath('1.preprint', null);
    }

    public function test_relationship_migration_rolls_back_and_reapplies(): void
    {
        $migration = require database_path('migrations/2026_11_09_000000_add_published_publication_id_to_publications.php');
        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('publications', 'published_publication_id'));
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('publications', 'published_publication_id'));
    }
}
