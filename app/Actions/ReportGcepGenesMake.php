<?php

namespace App\Actions;
use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Gene;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsController;

class ReportGcepGenesMake extends ReportMakeAbstract
{
    use AsController;
    use AsCommand;

    public $commandSignature = 'reports:gcep-genes';

    public function handle()
    {
        $genes  = Gene::whereHas('expertPanel', function ($q) {
            $q->typeGcep();
        })
        ->orderBy('gene_symbol')
        ->with([
            'expertPanel' => function ($q) {
                $q->select(['id', 'long_base_name', 'expert_panel_type_id']);
            },
            'expertPanel.type',
            'expertPanel.group' => function ($q) {
                $q->select(['id', 'group_type_id']);
            },
            'expertPanel.group.type'
        ])
        ->get();

        return $genes
            ->groupBy("hgnc_id")
            ->map(function ($group) {
                return [
                    'gene_symbol' => $group->first()->gene_symbol,
                    'hgnc_id' => $group->first()->hgnc_id,
                    'GCEPs' => $group->map(function ($g) {
                        return $g->expertPanel->full_long_base_name;
                    })->join(', ')
                ];
            })
            ->values()
            ->toArray();
    }
}