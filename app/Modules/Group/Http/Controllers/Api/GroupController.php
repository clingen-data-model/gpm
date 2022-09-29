<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use App\ModelSearchService;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Http\Resources\MemberResource;

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
                'group_type_id',
                'group_status_id',
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

        return $searchService->search($request->all());
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
        $members = $group->members->load('roles', 'permissions', 'cois', 'person', 'person.institution', 'person.credentials');
        return MemberResource::collection($members);
    }
}
