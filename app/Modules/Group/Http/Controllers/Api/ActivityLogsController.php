<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActivityLogsController extends Controller
{
    public function index(Request $request, $groupUuid)
    {
        $group = Group::where('uuid', $groupUuid)->sole();

        $logEntries = $group->logEntries()->with('causer')->get();
        
        return ['data' => $logEntries];
    }
}
