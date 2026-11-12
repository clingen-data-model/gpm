<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Support\PublicationTitle;
use App\Support\PublicationIdentity;
use App\Modules\Group\Models\Publication;
use App\Models\AnnualUpdate;

class PublicationUpdatesTest extends TestCase
{
    public function test_titles_are_plain_and_scientific_text_is_preserved(): void
    {
        foreach ([
            ['The <i>BRCA1</i> gene', 'The BRCA1 gene'],
            ['The &lt;i&gt;BRCA1&lt;/i&gt; gene', 'The BRCA1 gene'],
            ['A &amp; B &alpha; &gt;i&lt; C', "A & B \u{03B1} C"],
            ['Dose < 5 and > 2', 'Dose < 5 and > 2'],
            ['<script>alert(1)</script><b>Gene</b>', 'Gene'],
            ['&amp;lt;i&amp;gt;', '&lt;i&gt;'],
        ] as [$raw, $expected]) {
            $this->assertSame($expected, PublicationTitle::plain($raw));
        }
    }

    public function test_existing_metadata_is_cleaned_without_rewriting_storage(): void
    {
        $publication = new Publication(['meta' => ['title' => '&lt;i&gt;BRCA1&lt;/i&gt;']]);
        $raw = $publication->getAttributes()['meta'];
        $this->assertSame('BRCA1', $publication->meta['title']);
        $this->assertSame('BRCA1', $publication->toArray()['meta']['title']);
        $this->assertSame($raw, $publication->getAttributes()['meta']);
    }

    public function test_snapshot_titles_are_not_decoded_twice(): void
    {
        $review = new AnnualUpdate(['data' => ['publications' => [['title' => '&amp;lt;i&amp;gt;']]]]);
        $first = $review->data;
        $review->data = $first;
        $this->assertSame($first, $review->data);
    }

    public function test_unique_count_matches_identifier_aliases_across_groups(): void
    {
        $rows = [
            ['id'=>1, 'source'=>'pmcid', 'identifier'=>'PMC123', 'meta'=>['pmid'=>['id'=>'456'], 'doi'=>['id'=>'10.1/ABC']]],
            ['id'=>2, 'source'=>'pmid', 'identifier'=>'456', 'meta'=>[]],
            ['id'=>3, 'source'=>'doi', 'identifier'=>'https://doi.org/10.1/abc', 'meta'=>[]],
            ['id'=>4, 'source'=>'doi', 'identifier'=>'10.1/preprint', 'meta'=>[], 'published_publication_id'=>1],
            ['id'=>5, 'source'=>'pmcid', 'identifier'=>'PMC999', 'meta'=>[]],
        ];
        $this->assertSame(3, PublicationIdentity::count(array_map(fn($row)=>(object)$row, $rows)));
    }
    public function test_identifier_case_and_whitespace_are_normalized(): void
    {
        $rows = [
            (object)['id'=>1, 'source'=>'pmcid', 'identifier'=>' PMC123 ', 'meta'=>[]],
            (object)['id'=>2, 'source'=>'pmcid', 'identifier'=>'pmc123', 'meta'=>[]],
            (object)['id'=>3, 'source'=>'doi', 'identifier'=>' DOI:10.1/ABC ', 'meta'=>[]],
            (object)['id'=>4, 'source'=>'doi', 'identifier'=>'https://doi.org/10.1/abc', 'meta'=>[]],
        ];
        $this->assertSame(2, PublicationIdentity::count($rows));
    }

    public function test_missing_identifiers_do_not_merge_records(): void
    {
        $rows = [];
        foreach ([null, '', '  '] as $id => $identifier) {
            $rows[] = (object)['id'=>$id+1, 'source'=>'pmcid', 'identifier'=>$identifier, 'meta'=>[]];
        }
        $this->assertSame(3, PublicationIdentity::count($rows));
    }

    public function test_identifier_types_only_merge_when_metadata_establishes_aliases(): void
    {
        $a = (object)['id'=>1, 'source'=>'pmid', 'identifier'=>'123', 'meta'=>[]];
        $b = (object)['id'=>2, 'source'=>'doi', 'identifier'=>'123', 'meta'=>[]];
        $this->assertSame(2, PublicationIdentity::count([$a, $b]));
        $a->meta = ['doi'=>['id'=>'123']];
        $this->assertSame(1, PublicationIdentity::count([$a, $b]));
    }
}
