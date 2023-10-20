<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Http\Request;

class CdwgController extends Controller
{
    public function index(Request $request)
    {
        $cdwgs = Group::cdwg()->orderBy('name')->get();

        return $cdwgs;
    }
}
