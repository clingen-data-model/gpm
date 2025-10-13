<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EvidenceSummaryController extends Controller
{
    public function index(Request $request, $groupUuid)
    {
        $group = Group::where('uuid', $groupUuid)->sole();
        $group->expertPanel->load('evidenceSummaries.gene');
        $group->expertPanel->evidenceSummaries->pluck('gene')->filter()->each->append('gt_gene');
        
        return ['data' => $group->expertPanel->evidenceSummaries];
    }
}
