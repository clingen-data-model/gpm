<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Modules\ExpertPanel\Actions\AffiliationCreate;

class AffiliationController extends Controller
{
    /** GET /api/applications/{app_uuid}/affiliation */
    public function show(ExpertPanel $expertPanel): JsonResponse
    {
        $hasOpen = DB::table('am_affiliation_requests')->where('request_uuid', $expertPanel->uuid)->exists();
        return response()->json([
            'affiliation_id' => $ep->affiliation_id, // string('#####') or null
            'status' => $ep->affiliation_id ? 'complete' : ($hasOpen ? 'pending' : 'none'),
        ]);
    }

    public function store(Request $request, ExpertPanel $expertPanel, AffiliationCreate $create): JsonResponse
    {
        // if already have one, short-circuit
        if ($expertPanel->affiliation_id) {
            return response()->json([
                'message' => 'Affiliation already exists.',
                'affiliation_id' => (int) $expertPanel->affiliation_id,
            ], 409);
        }

        return $create->handle($expertPanel);
    }
}
