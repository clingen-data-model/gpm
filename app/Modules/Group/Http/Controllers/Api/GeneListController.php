<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Api\GtApiService;

class GeneListController extends Controller
{
    protected $gtApi;

    public function __construct(GtApiService $gtApi) {
        $this->gtApi = $gtApi;
    }

    public function index(Request $request, Group $group)
    {
        if (!$group->isEp) {
            throw new ModelNotFoundException('Only expert panels have gene lists.');
        }

        $genes = $group
                ->expertPanel
                ->genes()
                ->select('gene_symbol', 'hgnc_id', 'mondo_id', 'disease_name', 'id', 'moi', 'tier', 'date_approved', 'plan')
                ->get();

        $mondoIds = $genes->pluck('mondo_id')->filter()->unique()->values()->toArray();
        $diseaseData = $mondoIds ? $this->gtApi->getDiseasesByMondoIds($mondoIds) : [];

        // Map disease names back to genes
        $diseaseMap = collect($diseaseData ?? [])->mapWithKeys(function ($d) {
            return [$d['mondo_id'] => $d['name']];
        });

        $genes->transform(function ($gene) use ($diseaseMap) {
            $gene->disease_name = $diseaseMap[$gene->mondo_id] ?? null;
            return $gene;
        });

        return $genes;
    }
}
