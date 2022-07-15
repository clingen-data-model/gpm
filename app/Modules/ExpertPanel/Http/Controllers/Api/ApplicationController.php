<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\ModelSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Http\Resources\ExpertPanelResource;
use App\Http\Resources\ExpertPanelCollection;
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
        $search = new ModelSearchService(
            modelClass: ExpertPanel::class,
            defaultSelect: [
                'expert_panels.uuid',
                'expert_panels.id',
                'expert_panels.long_base_name',
                'expert_panels.affiliation_id',
                'expert_panels.expert_panel_type_id',
                'expert_panels.group_id',
                'expert_panels.current_step',
                'step_1_approval_date',
                'step_1_received_date',
                'step_2_approval_date',
                'step_3_approval_date',
                'step_4_received_date',
                'step_4_approval_date',
                'coi_code',
                'date_completed',
                'expert_panels.deleted_at'
            ],
            defaultWith: [
                'group' => function ($q) {
                    $q->select('uuid', 'id', 'parent_id', 'name', 'group_type_id');
                },
                'group.members' => function ($q) {
                    $q->select('id', 'group_id', 'person_id')
                        ->isActive()
                        ->whereHas('roles', function ($q) {
                            $q->where('name', 'coordinator');
                        });
                },
                'group.members.person' => function ($q) {
                    $q->select('id', 'uuid', 'first_name', 'last_name', 'email');
                },
                'group.parent' => function ($q) {
                    $q->select('id', 'parent_id', 'name', 'group_type_id');
                },
                'nextActions',
                'nextActions.assignee',
                'group.latestLogEntry' => function ($q) {
                    $q->select('created_at', 'description', 'subject_id', 'subject_type');
                },
                'group.latestSubmission'
            ],
            whereFunction: function ($query, $where) {
                foreach ($where as $key => $val) {
                    if ($key == 'since') {
                        $query->where('updated_at', '>', Carbon::parse($val));
                        continue;
                    }
                    $query->where($key, $val);
                }

                return $query;
            },
            sortFunction: function ($query, $field, $dir) {
                if ($field == 'name') {
                    $query->orderBy('long_base_name', $dir);
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

                return $query;
            }
        );

        return ExpertPanelResource::collection($search->search($request->all()));
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
        $expertPanel->load([
            'cdwg',
            'group',
            'group.parent',
            'group.documents',
            'type',
            'group.members',
            'group.members.person',
            'group.logEntries',
            'group.latestLogEntry',
            'nextActions'
        ]);
        if ($request->has('with')) {
            $expertPanel->load($request->with);
        }
        return new ExpertPanelResource($expertPanel);
    }
}
