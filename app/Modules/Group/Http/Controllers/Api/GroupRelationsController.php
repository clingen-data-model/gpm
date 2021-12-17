<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use App\ModelSearchService;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Http\Resources\MemberResource;

class GroupRelationsController extends Controller
{
    public function documents(Request $request, Group $group)
    {
        return $group->documents;
    }

    public function nextActions(Request $request, Group $group)
    {
        return $group->expertPanel->nextActions;
    }
}
