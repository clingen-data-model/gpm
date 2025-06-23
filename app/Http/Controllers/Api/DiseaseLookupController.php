<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use App\Services\GtApi\GtApiService;

class DiseaseLookupController extends Controller
{
    protected GtApiService $gtApi;

    public function __construct(GtApiService $gtApi)
    {
        $this->gtApi = $gtApi;
    }

    public function show($mondoId)
    {
        $validator = Validator::make(['mondo_id' => $mondoId], [
            'mondo_id' => 'required|regex:/(MONDO:)?\d{7}/'
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $mondo_id = strtolower($validator->validated()['mondo_id']);
        // return DB::connection(config('database.gt_db_connection'))->table('diseases')->where('mondo_id', $mondoId)->sole();
        try {
            $result = $this->gtApi->getDiseaseByMondoId($mondo_id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve disease data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {       

        $validator = Validator::make($request->all(), [
            'query_string' => ['required', 'string', 'min:3'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $query = strtolower($validator->validated()['query_string']);

        
        try {
            $result = $this->gtApi->searchDiseases($query);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to search disease data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
