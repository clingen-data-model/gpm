<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use App\ModelSearchService;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Http\Resources\MemberResource;

class GroupSubmissionsController extends Controller
{
    public function index(Request $request, Group $group)
    {
        return $group->submissions()
            ->with([
                'submitter' => function ($q) {
                    return $q->select([
                        'id', 
                        'first_name', 
                        'last_name',
                        'email',
                    ]);
                },
                'judgements',
                'judgements.person' => function ($q) {
                    return $q->select(['id', 'first_name', 'last_name', 'email']);
                }
            ])
            ->get();
    }

    public function latestSubmission(Group $group)
    {
        return $group->latestSubmission()
            ->with([
                'submitter' => function ($q) {
                    return $q->select([
                        'id', 
                        'first_name', 
                        'last_name',
                        'email',
                    ]);
                },
                'judgements',
                'judgements.person' => function ($q) {
                    return $q->select(['id', 'first_name', 'last_name', 'email']);
                }
            ])->first();
    }
    
}
