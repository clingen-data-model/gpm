<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\NextActionAssignee;
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