<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Http\Request;

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
                },
            ])
            ->orderBy('created_at')
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
                },
            ])->first();
    }
}
