<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\ExpertPanel\Models\NextActionAssignee;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class NextActionAssigneeController extends Controller
{
    public function index()
    {
        return Cache::rememberForever('next-action-assignees', function () {
            return NextActionAssignee::all();
        });
    }
}
