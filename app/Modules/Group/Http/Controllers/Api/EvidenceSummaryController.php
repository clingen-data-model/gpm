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
        if ($group->isVcepOrScvcep) {
            $pendingRemovalIds = \App\Modules\Group\Models\ScopeOfWorkChange::query()
                ->whereHas('version', fn ($version) => $version->forGroup($group)->activeRevision())
                ->where('rule_key', 'gene.remove')->get()
                ->map(fn ($change) => (int) ($change->before_value['id'] ?? 0));
            foreach ($group->expertPanel->evidenceSummaries as $summary) {
                // Live presentation only: do not append this state to historical snapshots.
                $summary->setAttribute('gene_scope_status', $summary->gene?->trashed()
                    ? ($pendingRemovalIds->contains($summary->gene_id) ? 'pending_removal' : 'removed')
                    : null);
            }
        }
        
        return ['data' => $group->expertPanel->evidenceSummaries];
    }
}
