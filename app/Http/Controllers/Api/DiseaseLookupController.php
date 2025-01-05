<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DiseaseLookupController extends Controller
{
    public function show($mondoId)
    {
        $validator = Validator::make(['mondo_id' => $mondoId], [
            'mondo_id' => 'required|regex:/(MONDO:)?\d{7}/'
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return DB::connection(config('database.gt_db_connection'))->table('diseases')->where('mondo_id', $mondoId)->sole();
    }

    public function search(Request $request)
    {
        $queryString = strtolower(($request->query_string ?? ''));
        if (strlen($queryString) < 3) {
            return [];
        }
        
        $results = DB::connection(config('database.gt_db_connection'))->table('diseases')
                    ->select('id', 'mondo_id', 'doid_id', 'name',)
                    ->where('name', 'like', '%'.$queryString.'%')
                    ->orWhere('mondo_id', 'like', '%'.$queryString.'%')
                    ->orWhere('doid_id', 'like', '%'.$queryString.'%')
                    ->limit(50)
                    ->get();

        return $results->toArray();
    }
}
