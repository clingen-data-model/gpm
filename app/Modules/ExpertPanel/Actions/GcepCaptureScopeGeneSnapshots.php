<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ScopeGeneSnapshot;
use App\Modules\Group\Models\Group;
use App\Services\Api\GtApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GcepCaptureScopeGeneSnapshots
{
    public function __construct(
        private readonly GtApiService $gtApiService
    ) {}

    public function handle(Group $group): void
    {
        $scopeGenes = $group->expertPanel->genes()->select('id', 'gene_symbol', 'hgnc_id')->get();
        if ($scopeGenes->isEmpty()) { return; }

        $symbols = $scopeGenes->pluck('gene_symbol')->filter()->map(fn ($s) => trim($s))->filter()->unique()->values();
        if ($symbols->isEmpty()) {
            ScopeGeneSnapshot::whereIn('scope_gene_id', $scopeGenes->pluck('id'))->delete();
            return;
        }

        $bulkGeneString = $symbols->implode(', ');
        $response = $this->gtApiService->lookupGenesBulk($bulkGeneString);
        $data = $response['data'] ?? [];

        $genesBySymbol = $scopeGenes->keyBy(fn ($g) => Str::upper(trim((string) $g->gene_symbol)));
        $genesByHgncId = $scopeGenes->keyBy('hgnc_id');
        
        $now = now();
        $rows = $seen = [];

        foreach ($data as $item) {
            $itemSymbol = Str::upper(trim((string) ($item['gene_symbol'] ?? '')));
            $itemHgncId = $item['hgnc_id'] ?? null;

            $scopeGene = null;

            if ($itemSymbol !== '' && $genesBySymbol->has($itemSymbol)) {
                $scopeGene = $genesBySymbol->get($itemSymbol);
            } elseif (!is_null($itemHgncId) && $genesByHgncId->has($itemHgncId)) {
                $scopeGene = $genesByHgncId->get($itemHgncId);
            }

            if (!$scopeGene) { continue; }
            $curationUUID = $item['curation_id'] ?? null;
            if (! $curationUUID) { continue; }
            $dedupeKey = $scopeGene->id . '|' . $curationUUID;
            if (isset($seen[$dedupeKey])) { continue; }
            $seen[$dedupeKey] = true;

            $rows[] = [
                'scope_gene_id' => $scopeGene->id,
                'curation_uuid' => $curationUUID,
                'check_key'     => $item['checkKey'] ?? null,
                'payload'       => json_encode($item, JSON_UNESCAPED_SLASHES),
                'captured_at'   => $now,
                'is_outdated'   => false,
                'last_compared_at' => null,
            ];
        }


        DB::transaction(function () use ($scopeGenes, $rows) {
            $ids = $scopeGenes->pluck('id');
            ScopeGeneSnapshot::whereIn('scope_gene_id', $ids)->delete();
            if (!empty($rows)) {
                ScopeGeneSnapshot::insert($rows);
            }
        });
    }
}