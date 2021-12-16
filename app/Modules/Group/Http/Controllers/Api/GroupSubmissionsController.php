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
        return $group->submissions;
    }
}
