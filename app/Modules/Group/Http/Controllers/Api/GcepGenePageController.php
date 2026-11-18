<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use App\Services\Api\GtApiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GcepGenePageController extends Controller
{
    public function __invoke(Request $request, Group $group, GtApiService $gt)
    {
        abort_unless($group->is_gcep, 404);
        $input = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', Rule::in([20, 50, 100])],
            'sort' => ['sometimes', Rule::in(['gene_symbol', 'tier'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'enrich' => ['sometimes', 'boolean'],
        ]);
        $query = $group->expertPanel->genes()->select('id', 'hgnc_id', 'gene_symbol', 'tier', 'date_approved');
        $sort = $input['sort'] ?? 'gene_symbol';
        $direction = $input['direction'] ?? 'asc';
        if ($sort === 'tier') {
            $query->orderByRaw('COALESCE(tier, 0) '.$direction);
        } else {
            $query->orderBy('gene_symbol', $direction);
        }
        $page = $query->orderBy('id')->paginate($input['per_page'] ?? 20);
        // GCEP clients with complete per-gene results can reuse them after local mutations.
        // The default response contract remains enriched.
        if (!$request->boolean('enrich', true)) {
            return $page;
        }
        try {
            $curations = collect($gt->lookupGcepGenesComplete($page->pluck('gene_symbol')->all())['data'])
                ->groupBy(fn ($row) => strtoupper(trim($row['gene_symbol'])));
        } catch (\Throwable $error) {
            report($error);
            return response()->json(['message' => 'Could not retrieve complete GeneTracker curations. Please retry.'], 502);
        }
        $page->setCollection($page->getCollection()->map(function ($gene) use ($curations) {
            $entries = $curations->get(strtoupper(trim($gene->gene_symbol)), collect());
            return array_merge($gene->toArray(), [
                'details' => $entries->values()->all(),
                'statuses' => $entries->isEmpty() ? ['Not Curated'] : $entries->map(fn ($r) => ($r['curation_status'] ?? null) ?: 'Unknown')->unique()->values()->all(),
                'expert_panels' => $entries->pluck('expert_panel')->filter()->unique()->values()->all(),
            ]);
        }));
        return $page;
    }
}
