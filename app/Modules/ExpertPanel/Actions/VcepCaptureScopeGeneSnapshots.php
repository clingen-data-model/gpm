<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ScopeGeneSnapshot;
use App\Modules\Group\Models\Group;
use App\Services\Api\GtApiService;
use Illuminate\Support\Facades\DB;

class VcepCaptureScopeGeneSnapshots
{
    public function __construct(
        private readonly GtApiService $gtApiService
    ) {}

    public function handle(Group $group): void
    {
        $scopeGenes = $group->expertPanel->genes()->select('id', 'gt_curation_uuid')->whereNotNull('gt_curation_uuid')->get();
        if ($scopeGenes->isEmpty()) { return; }

        $now = now();
        $rows = [];

        DB::transaction(function () use ($scopeGenes, $rows) {
            foreach ($scopeGenes as $scopeGene) {
                $uuid = (string) $scopeGene->gt_curation_uuid;
                if (!$uuid) { continue; }

                $resp = $this->gtApiService->getCurationByID($uuid);
                $data = $resp['data'] ?? [];

                $item = null;

                if (!empty($data)) {
                    foreach ($data as $d) {
                        if (($d['curation_id'] ?? null) === $uuid) {
                            $item = $d;
                            break;
                        }
                    }
                    $item ??= $data[0];
                }
                $curationUUID = $item['curation_id'] ?? $uuid;
                ScopeGeneSnapshot::updateOrCreate(
                    [
                        'scope_gene_id' => $scopeGene->id,
                        'curation_uuid' => $curationUUID,
                    ], [
                        'check_key'         => $item['checkKey'] ?? null,
                        'payload'           => json_encode($item, JSON_UNESCAPED_SLASHES),
                        'captured_at'       => $now,
                        'is_outdated'       => false,
                        'last_compared_at'  => null,
                ]);
            }
        });
    }
}
