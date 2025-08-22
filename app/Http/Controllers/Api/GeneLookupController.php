<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Services\Api\GtApiService;

class GeneLookupController extends Controller
{
    protected GtApiService $gtApi;

    public function __construct(GtApiService $gtApi)
    {
        $this->gtApi = $gtApi;
    }

    public function search(Request $request)
    {
        $queryString = strtolower(($request->query_string ?? ''));
        
        if (strlen($queryString) < 3) {
            return [];
        }
        return $this->gtApi->searchGenes($queryString);
    }

    public function check(Request $request)
    {
        $symbols = $request->input('gene_symbol') ?? '';
        return $this->gtApi->lookupGenesBulk($symbols);
    }

    public function curations(Request $request)
    {        
        $query = $request->get('query');

        if (! $query || strlen($query) < 3) {
            return response()->json([], 200);
        }

        return $this->gtApi->searchCurations($query);
    }

    public function curationids(Request $request)
    {        
        $curationIDs = $request->input('curation_ids') ?? '';
        return $this->gtApi->getCurationByID($curationIDs);
    }
}