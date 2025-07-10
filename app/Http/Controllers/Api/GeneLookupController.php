<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

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
        $results = DB::connection(config('database.gt_db_connection'))->table('genes')
                        ->where('gene_symbol', 'like', '%'.$queryString.'%')
                        ->orWhere('hgnc_id', 'like', '%'.$queryString.'%')
                        ->limit(250)
                        ->get();

        return $results->toArray();
    }
    
    
}
