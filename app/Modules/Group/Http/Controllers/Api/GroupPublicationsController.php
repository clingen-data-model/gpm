<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Http\Request;

class GroupPublicationsController extends Controller
{
    public function search(Request $request, Group $group)
    {
        $this->authorize('view', $group);
        $data = $request->validate(['query' => 'required|string|min:2|max:250']);
        return \App\Modules\Group\Models\Publication::with('group:id,uuid,name')
            ->whereHas('group')->where(function ($q) use ($data) {
                $title = $q->getQuery()->getGrammar()->wrap('meta->title');
                $term = '%'.$data['query'].'%';
                $q->whereRaw('LOWER(identifier) LIKE LOWER(?)', [$term])
                    ->orWhereRaw("LOWER($title) LIKE LOWER(?)", [$term]);
            })->orderBy('id')->limit(30)->get();
    }

    public function index(Request $request, Group $group)
    {
        $this->authorize('view', $group);

        $q = $group->publications()->with(['publishedPublication.group:id,uuid,name', 'preprint.group:id,uuid,name'])->orderByDesc('published_at')->orderByDesc('id');

        // Optional filters (?type=preprint|published, ?status=pending|enriched|failed, ?start=YYYY-MM-DD, ?end=YYYY-MM-DD)
        if($type = $request->query('type')) { $q->where('pub_type', $type); }
        if($status = $request->query('status')) { $q->where('status', $status); }
        if($start = $request->query('start')) { $q->whereDate('published_at', '>=', $start); }
        if($end = $request->query('end')) { $q->whereDate('published_at', '<=', $end); }

        if ($request->boolean('shape_exchange')) { // For Annual Updates
            return $q->get()->map->toExchangePayload();
        }
        // Group list only: annual reviews above retain both publication records.
        return $q->whereNull('published_publication_id')->get();
    }
}
