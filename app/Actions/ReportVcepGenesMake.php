<?php

namespace App\Actions;
use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Gene;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsController;

class ReportVcepGenesMake
{
    use AsController;

    public $commandSignature = 'reports:gcep-genes';

    public function __construct(private TransformArrayForCsv $csvTransformer)
    {
    }

    public function handle()
    {
        return $this->pullData();
    }

    public function asController(ActionRequest $request)
    {
        $data = $this->handle();

        if ($request->header('accept') == 'application/json') {
            return $data;
        }

        $data = $this->csvTransformer->handle($this->handle());
        return response($data, 200, ['Content-type' => 'text/csv']);
    }

    public function asCommand(Command $command)
    {
        dump(collect($this->handle()));
    }


    private function pullData(): array
    {
        $genes  = Gene::whereHas('expertPanel', function ($q) {
                        $q->typeVcep();
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
        $gtApi = app(\App\Services\Api\GtApiService::class);

        $mondoIds = $genes->pluck('mondo_id')->unique()->values()->all();
        $diseaseData = $gtApi->getDiseasesByMondoIds($mondoIds);

        // dd($diseaseData);
        $diseaseMap = collect($diseaseData['data'])
                        ->keyBy('mondo_id')
                        ->map(fn($d) => (object)[
                            'mondo_id' => $d['mondo_id'],
                            'name' => $d['name']
                        ]);
        $genes->each(function ($gene) use ($diseaseMap) {
            $gene->setRelation('disease', $diseaseMap[$gene->mondo_id] ?? (object)[]);
        });

        return $genes
            ->groupBy(function ($g) {
                return $g->hgnc_id.'-'.$g->mondo_id;
            })
            ->map(function ($group) {
                return [
                    'gene_symbol' => $group->first()->gene_symbol,
                    'hgnc_id' => $group->first()->hgnc_id,
                    'disease' => $group->first()->disease->name,
                    'mondo_id' => $group->first()->mondo_id,
                    'VCEPs' => $group->map(function ($g) {
                        return $g->expertPanel->full_long_base_name;
                    })->join(', ')
                ];
            })
            ->values()
            ->toArray();
    }

}
