<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\Group\Models\ScopeOfWorkVersion;

class ScopeOfWorkChangeDisplay
{
    public static function geneContext(ScopeOfWorkChange $change, ScopeOfWorkVersion $version): ?array
    {
        if ($change->rule_key !== 'gene.update' || !in_array($change->field_name, ['mondo_id', 'disease_name'], true)) {
            return null;
        }
        // Never use live gene data to fill gaps in a recorded change.
        $gene = function (?array $snapshot, $id): array {
            if ($id === null) return [];
            $captured = collect(data_get($snapshot, 'scope_of_work.scope_genes', []))->firstWhere('id', $id) ?? [];
            return array_intersect_key($captured, array_flip(['gene_symbol', 'mondo_id', 'disease_name']));
        };
        return [
            'before' => $gene($version->baseVersion?->latestSnapshot?->snapshot, data_get($change->before_value, 'id')),
            'after' => $gene($version->latestSnapshot?->snapshot, data_get($change->after_value, 'id')),
        ];
    }
}
