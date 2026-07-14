<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Gene;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsAction;

class ReportScvcepGenesMake
{
    use AsAction;

    public $commandSignature = 'reports:scvcep-genes';

    public function __construct(private TransformArrayForCsv $csvTransformer)
    {
    }

    public function handle(): array
    {
        return $this->pullData();
    }

    public function asController(ActionRequest $request)
    {
        $data = $this->handle();

        if ($request->expectsJson()) {
            return $data;
        }

        $csv = $this->csvTransformer->handle($data);
        return response($csv, 200, ['Content-Type' => 'text/csv']);
    }

    public function asCommand(Command $command): void
    {
        dump(collect($this->handle()));
    }

    private function pullData(): array
    {
        $genes  = Gene::query()->whereHas('expertPanel', function ($q) {
                $q->typeScvcep();
            })
            ->with([
                'expertPanel' => function ($q) {
                    $q->select(['id', 'long_base_name', 'expert_panel_type_id']);
                },
                'expertPanel.type',
            ])
            ->orderBy('gene_symbol')
            ->get();

        $mondoIds = $genes->pluck('mondo_id')->filter()->unique()->values()->all();
        $diseaseData = [];

        if (count($mondoIds) > 0) {
            $gtApi = app(\App\Services\Api\GtApiService::class);
            $diseaseData = $gtApi->getDiseasesByMondoIds($mondoIds);
        }

        $diseaseMap = collect($diseaseData ?? [])
            ->filter(fn ($disease) => !empty($disease['mondo_id']))
            ->keyBy('mondo_id');

        return $genes
            ->groupBy(function ($gene) {
                return $gene->hgnc_id.'-'.$gene->mondo_id;
            })
            ->map(function ($group) use ($diseaseMap) {
                $gene = $group->first();
                $diseaseName = data_get($diseaseMap->get($gene->mondo_id), 'name') ?? $gene->disease_name ?? '';
                $scvceps = $group->map(fn ($item) => $item->expertPanel?->full_long_base_name)->filter()->unique()->sort()->values()->join(', ');

                return [
                    'gene_symbol' => $gene->gene_symbol,
                    'hgnc_id' => $gene->hgnc_id,
                    'disease' => $diseaseName,
                    'mondo_id' => $gene->mondo_id,
                    'SC-VCEPs' => $scvceps,
                ];
            })
            ->values()
            ->toArray();
    }
}