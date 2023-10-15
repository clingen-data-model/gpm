<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneLookupController extends Controller
{
    public function show($hgncId)
    {
        return DB::connection(config('database.gt_db_connection'))->table('hgnc_genes')->where('hgnc_id', $hgncId)->sole();
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
