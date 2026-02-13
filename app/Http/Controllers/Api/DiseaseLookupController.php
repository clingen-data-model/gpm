<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\Api\GtApiService;

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
        return $this->gtApi->getDiseasesByMondoIds($mondo_id);
    }

    public function search(Request $request)
    {       
        $queryString = strtolower(($request->query_string ?? ''));
        if (strlen($queryString) < 3) {
            return [];
        }
        
        return $this->gtApi->searchDiseases($queryString);
    }
}