<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemInfoController extends Controller
{
    public function index()
    {
        $data = [
            'build' => config('app.build'),
            'env' => config('app.env'),
            'app' => [
                'name' => config('app.name')
            ]
        ];
        return $data;
    }
}
