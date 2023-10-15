<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Http\Resources\ChildGroupResource;
use App\Modules\Group\Models\Group;
use Illuminate\Http\Request;

class GroupRelationsController extends Controller
{
    public function children(Group $group)
    {
        $children = $group->children()
            ->withCount('members')
            ->with([
                'type',
                'status',

                'expertPanel',
                'expertPanel.type',

                'coordinators',
                'coordinators.person',

                'chairs',
                'chairs.person',
            ])
            ->get()
            ->sortBy('expertPanel.name');

        return ChildGroupResource::collection($children);
    }

    public function documents(Request $request, Group $group)
    {
        return $group->documents()->with('type')->get();
    }

    public function nextActions(Request $request, Group $group)
    {
        return $group->expertPanel->nextActions;
    }

    public function tasks(Request $request, Group $group)
    {
        $query = $group->tasks();
        if ($request->has('pending')) {
            $query->pending();
        } elseif ($request->has('completed')) {
            $query->completed();
        }

        return $query->get();
    }
}
