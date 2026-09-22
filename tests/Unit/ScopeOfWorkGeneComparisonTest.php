<?php

namespace Tests\Unit;

use App\Modules\Group\Services\ScopeOfWorkDisplayComparison;
use PHPUnit\Framework\TestCase;

class ScopeOfWorkGeneComparisonTest extends TestCase
{
    private function compare(array $before, array $after): array
    {
        return (new ScopeOfWorkDisplayComparison)->handle(
            ['scope_of_work' => ['scope_genes' => $before]],
            ['relations' => ['expertPanel' => ['relations' => ['genes' => array_map(
                fn ($gene) => ['attributes' => $gene], $after,
            )]]], 'attributes' => []],
        )['rows']['genes'];
    }

    public function test_retains_captured_fields_and_normalizes_equivalent_values(): void
    {
        $gene = ['id' => 12, 'gene_symbol' => 'GENE', 'hgnc_id' => 10,
            'mondo_id' => 'MONDO:1', 'disease_name' => 'Disease', 'disease_entity' => null,
            'moi' => 'AD', 'tier' => 1, 'plan' => ['classification' => 'Definitive'],
            'date_approved' => '2026-01-01T00:00:00.000000Z', 'gt_curation_uuid' => 'curation'];
        $equivalent = array_replace($gene, ['id' => '12', 'hgnc_id' => '10', 'tier' => '1',
            'plan' => json_encode($gene['plan']), 'date_approved' => '2026-01-01 00:00:00']);
        $row = $this->compare([$gene], [$equivalent])[0];
        $this->assertSame('12', $row['key']);
        $this->assertSame('unchanged', $row['operation']);
        $this->assertSame([], $row['field_changes']);
        $this->assertSame([], $row['unavailable_fields']);
        foreach (array_keys($gene) as $field) {
            $this->assertArrayHasKey($field, $row['before']);
            $this->assertArrayHasKey($field, $row['after']);
        }
        $changed = $this->compare([$gene], [array_replace($equivalent, ['tier' => 2])])[0];
        $this->assertSame('changed', $changed['operation']);
        $this->assertSame([['field' => 'tier', 'before' => '1', 'after' => '2']], $changed['field_changes']);
    }

    public function test_missing_fields_stay_unavailable_and_explicit_null_is_compared(): void
    {
        $old = ['id' => 1, 'gene_symbol' => 'GENE', 'moi' => null];
        $new = $old + ['tier' => 2];
        $new['moi'] = 'AD';
        $row = $this->compare([$old], [$new])[0];
        $this->assertArrayNotHasKey('tier', $row['before']);
        $this->assertContains('tier', $row['unavailable_fields']);
        $this->assertContains('plan', $row['unavailable_fields']);
        $this->assertSame([['field' => 'moi', 'before' => null, 'after' => 'AD']], $row['field_changes']);
    }
}
