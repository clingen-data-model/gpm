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
        $data = $request->validate([
            'query' => 'nullable|string|max:250',
            'publication_id' => 'nullable|integer',
        ]);
        $current = isset($data['publication_id'])
            ? $group->publications()->findOrFail($data['publication_id']) : null;
        $query = trim($data['query'] ?? '');
        $candidates = $group->publications()->with('group:id,uuid,name')
            ->whereNull('published_publication_id')->whereDoesntHave('preprint');
        if ($current) $candidates->where('id', '!=', $current->id);
        if ($query !== '') {
            $candidates->where(function ($q) use ($query) {
                $title = $q->getQuery()->getGrammar()->wrap('meta->title');
                $term = '%'.$query.'%';
                $q->whereRaw('LOWER(identifier) LIKE LOWER(?)', [$term])
                    ->orWhereRaw("LOWER($title) LIKE LOWER(?)", [$term]);
            });
        }
        $candidates->orderByDesc('created_at')->orderByDesc('id');
        if ($query === '') $candidates->limit(10);
        return $candidates->get();
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
            return $q->whereNull('published_publication_id')->whereNull('annual_update_id')->get()->map->toAnnualUpdatePayload();
        }
        // Default group list hides superseded preprints but retains reported articles.
        if (!$request->boolean('include_linked')) $q->whereNull('published_publication_id');
        return $q->get();
    }
}
