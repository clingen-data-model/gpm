<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Http\Request;

class GroupPublicationsController extends Controller
{
    public function index(Request $request, Group $group)
    {
        $this->authorize('view', $group);

        $q = $group->publications()->orderByDesc('published_at')->orderByDesc('id');

        // Optional filters (?type=preprint|published, ?status=pending|enriched|failed, ?start=YYYY-MM-DD, ?end=YYYY-MM-DD)
        if($type = $request->query('type')) { $q->where('pub_type', $type); }
        if($status = $request->query('status')) { $q->where('status', $status); }
        if($start = $request->query('start')) { $q->whereDate('published_at', '>=', $start); }
        if($end = $request->query('end')) { $q->whereDate('published_at', '<=', $end); }

        // Return paginator to match other list endpoints
        return $q->paginate($request->integer('per_page', 50));
    }
}
