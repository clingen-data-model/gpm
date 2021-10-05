<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        return Group::with(['type', 'status'])->get();
    }

    public function show($uuid, Request $request)
    {
        $group = Group::findByUuidOrFail($uuid);
        $group->load(['members', 'members.person', 'members.roles', 'members.permissions']);

        return $group;
    }
}
