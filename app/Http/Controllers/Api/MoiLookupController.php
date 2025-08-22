<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\GtApiService;

class MoiLookupController extends Controller
{
    protected GtApiService $gtApi;

    public function __construct(GtApiService $gtApi)
    {
        $this->gtApi = $gtApi;
    }

    public function index(Request $request)
    {
        return $this->gtApi->mois();
    }

}