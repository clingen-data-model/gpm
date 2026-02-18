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
        try {
            $response = $this->gtApi->searchGenes($queryString);

            if (!($response['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to search genes.',
                    'errors' => $response['message'] ?? 'Unknown error'
                ], 500);
            }

            return $response['data']['results'] ?? [];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function check(Request $request)
    {
        $symbols = $request->input('gene_symbol');

        if (!$symbols || !is_string($symbols)) {
            return response()->json([
                'success' => false,
                'message' => 'gene_symbol must be a non-empty comma-separated string.',
                'data' => []
            ], 422);
        }

        try {
            $result = $this->gtApi->lookupGenesBulk($symbols);

            return response()->json([
                'success' => true,
                'message' => 'Gene status retrieved.',
                'data' => $result['data'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking gene status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
