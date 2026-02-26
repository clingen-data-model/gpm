<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;

class CdwgController extends Controller
{
    public function index(Request $request)
    {
        $scope = strtolower($request->query('scope', 'cdwg'));
        $query = match($scope) {
            'sc', 'sc-cdwg' => Group::sccdwg(),
            'wg'            => Group::wg(),
            default         => Group::cdwg(),
        };

        return $query->orderBy('name', 'asc')->get(['id', 'name']);
    }
}
