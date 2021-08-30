<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;

class CdwgController extends Controller
{
    public function index(Request $request)
    {
        $cdwgs = Group::cdwg()->orderBy('name', 'asc')->get();
        return $cdwgs;
    }
}
