<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\ExpertPanel\Models\Gene;

class ReportGcepGenesMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:gcep-genes';

    public function csvHeaders(): ?array
    {
        return ['gene_symbol','hgnc_id','GCEPs'];
    }

    public function streamRows(callable $push): void
    {
        $connection = DB::connection();
        $queryLogEnabled = $connection->logging();
        $connection->disableQueryLog();
        try {
            $currentHgnc = null;
            $currentSymbol = null;
            $eps = [];

            $this->baseQuery()
                ->orderBy('hgnc_id')
                ->orderBy('id')
                ->chunk(2000, function ($genes) use (&$currentHgnc, &$currentSymbol, &$eps, $push) {
                    foreach ($genes as $g) {
                        if ($currentHgnc !== null && $g->hgnc_id !== $currentHgnc) {
                            $push([
                                'gene_symbol' => $currentSymbol,
                                'hgnc_id'     => $currentHgnc,
                                'GCEPs'       => implode(', ', $eps),
                            ]);
                            $eps = [];
                        }

                        $currentHgnc = $g->hgnc_id;
                        $currentSymbol = $g->gene_symbol;
                        $eps[] = $g->expertPanel->full_long_base_name;
                    }

                    $genes->each->unsetRelations();
                    gc_collect_cycles();
                });

            if ($currentHgnc !== null) {
                $push([
                    'gene_symbol' => $currentSymbol,
                    'hgnc_id'     => $currentHgnc,
                    'GCEPs'       => implode(', ', array_values(array_unique($eps))),
                ]);
            }
        } finally {
            if ($queryLogEnabled) {
                $connection->enableQueryLog();
            }
        }
    }

    private function baseQuery()
    {
        return Gene::query()
            ->whereHas('expertPanel', fn($q) => $q->typeGcep())
            ->select(['id','gene_symbol','hgnc_id','expert_panel_id'])
            ->with([
                'expertPanel' => fn($q) => $q->select(['id','long_base_name','expert_panel_type_id','group_id']),
                'expertPanel.type:id,name,display_name',
                'expertPanel.group:id,group_type_id',
                'expertPanel.group.type:id,name,display_name',
            ]);
    }
}
