<?php
namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Actions\ReportSummaryMake;
use App\Actions\ReportPublicationsMake;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use Illuminate\Support\Facades\Cache;

class PublicationCountTest extends TestCase
{
    public function test_summary_deduplicates_records_while_export_preserves_group_rows(): void
    {
        $groups = Group::factory()->count(2)->create();
        foreach ([$groups[0], $groups[1], $groups[0]] as $group) {
            Publication::create(['group_id'=>$group->id, 'source'=>'pmcid', 'identifier'=>'PMC123',
                'meta'=>['title'=>'Shared article', 'pmcid'=>['id'=>'PMC123']]]);
        }
        $article = Publication::first();
        $preprint = Publication::create(['group_id'=>$groups[0]->id, 'source'=>'doi', 'identifier'=>'10.1/preprint',
            'meta'=>['title'=>'Shared article', 'doi'=>['id'=>'10.1/preprint']]]);
        $preprint->published_publication_id = $article->id;
        $preprint->save();
        $deleted = Publication::create(['group_id'=>$groups[0]->id, 'source'=>'pmcid', 'identifier'=>'PMC999']);
        $deleted->delete();
        Cache::forget('report:basic-summary:v2');
        $metrics = [];
        app(ReportSummaryMake::class)->streamRows(function ($row) use (&$metrics) {
            $metrics[$row['Metric']] = $row['Value'];
        });
        $export = app(ReportPublicationsMake::class)->handle();
        $this->assertCount(4, $export);
        $this->assertSame(4, $metrics['Number of Publications']);
        $this->assertSame(2, $metrics['Unique publications']);
        $distinct = collect($export)->map(fn($row)=>json_encode($row['identifiers']))->unique()->count();
        $this->assertSame($distinct, $metrics['Unique publications']);
        $this->assertCount(2, collect($export)->pluck('group_uuid')->unique());
    }
}
