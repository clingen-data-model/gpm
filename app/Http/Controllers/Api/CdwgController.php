<?php

namespace App\Http\Controllers\Api;

use App\Models\Cdwg;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CdwgController extends Controller
{
    public function index(Request $request)
    {
        $cdwgs = Cdwg::orderBy('name', 'asc')->get();
        return $cdwgs;
    }
    
}
