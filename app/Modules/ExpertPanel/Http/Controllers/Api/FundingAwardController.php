<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;

class FundingAwardController extends Controller
{
    public function index(ExpertPanel $expertPanel)
    {
        return FundingAward::query()
            ->where('expert_panel_id', $expertPanel->id)
            ->with(['fundingSource.fundingType'])
            ->orderByRaw('start_date IS NULL') // nulls last
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }
}
