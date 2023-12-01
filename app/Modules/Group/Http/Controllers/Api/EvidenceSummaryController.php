<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Http\Request;

class EvidenceSummaryController extends Controller
{
    public function index(Request $request, $groupUuid)
    {
        $group = Group::where('uuid', $groupUuid)->sole();
        $group->expertPanel->load('evidenceSummaries', 'evidenceSummaries.gene', 'evidenceSummaries.gene.gene');

        return ['data' => $group->expertPanel->evidenceSummaries];
    }
}
