<?php

namespace App\Modules\Funding\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Funding\Models\FundingType;

class FundingTypeController extends Controller
{
    public function index()
    {
        return FundingType::query()->orderBy('name')->get(['id', 'name']);
    }
}
