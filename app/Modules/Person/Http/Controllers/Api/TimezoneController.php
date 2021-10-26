<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class TimezoneController extends Controller
{
    public function index()
    {
        return ['data' => timezone_identifiers_list()];
    }
}
