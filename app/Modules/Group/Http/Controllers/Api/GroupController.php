<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\ModelSearchService;
use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Http\Resources\GroupSummaryResource;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $searchService = new ModelSearchService(
            modelClass: Group::class,
            defaultSelect: [
                'id',
                'uuid',
                'name',
                'description',
                'group_type_id',
                'group_status_id',
                'coi_code'
            ],
            defaultWith: [
                'type',
                'status',
                'expertPanel' => function ($query) {
                    $query->select([
                        'id',
                        'uuid',
                        'long_base_name',
                        'short_base_name',
                        'expert_panel_type_id',
                        'group_id',
                        'step_1_approval_date',
                        'step_2_approval_date',
                        'step_3_approval_date',
                        'step_4_approval_date',
                        'date_completed',
                        'current_step'
                    ]);
                },
                'expertPanel.group',
                'expertPanel.group.type',
                'coordinators' => function ($query) {
                    $query->select(['person_id', 'group_id', 'id']);
                },
                'coordinators.roles' => function ($query) {
                    $query->select(['id', 'name']);
                },
                'coordinators.person' => function ($query) {
                    $query->select(['id', 'email', 'first_name', 'last_name', 'user_id', 'uuid']);
                },
            ],
            sortFunction: function ($query, $field, $dir) {
                if ($field == 'type') {
                    $query->innerJoin('group_types', 'groups.group_type_id', '=', 'group_types.id');
                    $query->orderBy('group_types.name', $dir);
                    return $query;
                }
                if ($field == 'status') {
                    $query->innerJoin('group_statuses', 'groups.group_status_id', '=', 'group_statuses.id');
                    $query->orderBy('group_statuses.name', $dir);
                    return $query;
                }
                return $query->orderBy($field, $dir);
            }
        );

        $results = $searchService->search($request->all());
        JsonResource::withoutWrapping();
        return GroupSummaryResource::collection($results);
    }

    public function show($uuid, Request $request)
    {
        $group = Group::findByUuidOrFail($uuid);
        $group->load([
            'type',
            'status',
            'members',
            'members.person',
            'members.roles',
            'members.permissions',
            'members.cois',
            'parent',
            'expertPanel',
            'expertPanel.curationReviewProtocol'
        ]);

        return new GroupResource($group);
    }

    public function members(Request $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $members = $group->members->load(
            'roles',
            'permissions',
            'cois',
            'latestCoi',
            'person',
            'person.institution',
            'person.credentials',
            'person.expertises',
            'person.country',
        );
        return MemberResource::collection($members);
    }
}
