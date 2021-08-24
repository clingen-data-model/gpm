<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ApplicationController extends Controller
{
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ExpertPanel::query()
                    ->select('expert_panels.*')
                    ->with('logEntries', 'nextActions', 'group', 'group.parent');

        if ($request->has('sort')) {
            $field = $request->sort['field'];
            $dir = $request->sort['dir'] ?? 'asc';

            if ($field == 'name') {
                $query->leftJoin('groups', 'expert_panels.group_id', '=', 'groups.id');
                $query->orderBy('groups.name', $dir);
            } elseif ($field == 'cdwg.name') {
                $query->leftJoin('groups', 'expert_panels.group_id', '=', 'groups.id');
                $query->leftJoin('groups as parents', 'groups.parent_id', '=', 'parents.id');
                $query->orderBy('parents.name', $dir);
            } elseif ($field == 'latestLogEntry.created_at') {
                $subQuery = DB::table('activity_log')
                                ->select('subject_id', DB::raw('MAX(created_at) as latest_activity_at'))
                                ->where('subject_type', ExpertPanel::class)
                                ->groupBy('subject_id');

                $query->leftJoinSub($subQuery, 'latest_activity', function ($join) {
                    $join->on('latest_activity.subject_id', '=', 'expert_panels.id');
                })
                ->addSelect('latest_activity.latest_activity_at as latest_activity_at')
                ->orderBy('latest_activity_at', $dir);
            } else {
                $query->orderBy($field, $dir);
            }
        }

        if ($request->has('with')) {
            $realRelationships = [];
            $query->with($request->with);
        }

        if ($request->has('where')) {
            foreach ($request->where as $key => $value) {
                if ($key == 'since') {
                    $query->where('updated_at', '>', Carbon::parse($value));
                    continue;
                }
                $query->where($key, $value);
            }
        }

        // if ($request->has('showDeleted')) {
        $query->withTrashed();
        // }

        $expertPanels = $query->paginate(20);

        // $expertPanels = $query->get();


        return $expertPanels;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($uuid, Request $request)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($uuid);
        $expertPanel->load(['latestLogEntry', 'group', 'group.parent', 'type', 'contacts', 'nextActions']);
        $expertPanel->contacts;
        if ($request->has('with')) {
            $expertPanel->load($request->with);
        }
        return $expertPanel;
    }
}
