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
            ->with(['fundingSource.fundingType', 'contactPis'])
            ->orderByRaw('start_date IS NULL') // nulls last
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }
    
    public function piOptions(ExpertPanel $expertPanel)
    {
        $members = $expertPanel->group->activeMemberships()->with('person:id,uuid,first_name,last_name,email,phone')->get();
        $people = $members->pluck('person')->filter()
                    ->unique('id')
                    ->sortBy(fn ($p) => strtolower(($p->last_name ?? '').' '.($p->first_name ?? '')))
                    ->values()->map(fn ($p) => [
                            'id'    => $p->id,
                            'uuid'  => $p->uuid,
                            'name'  => trim(($p->first_name ?? '').' '.($p->last_name ?? '')),
                            'email' => $p->email,
                            'phone' => $p->phone,
                        ]);

        return $people;
    }

}
