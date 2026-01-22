<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Api\GtApiService;
use App\Modules\ExpertPanel\Models\ScopeGeneSnapshot;

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
            ->select('id', 'hgnc_id', 'gene_symbol', 'mondo_id', 'disease_name', 'moi', 'tier', 'plan', 'date_approved')
            // ->with(['snapshots' => function ($q) {
            //         $q->select('scope_gene_id', 'curation_uuid', 'check_key', 'payload')->orderByDesc('captured_at');
            //     }]) FOR FUTURE USE
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
